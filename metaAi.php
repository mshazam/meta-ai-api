<?php
// Custom Exception Classes
class FacebookInvalidCredentialsException extends Exception {}
class FacebookRegionBlockedException extends Exception {}

// Helper Functions
function generateOfflineThreadingId(): string {
    $maxInt = (1 << 64) - 1;
    $mask22Bits = (1 << 22) - 1;

    $timestamp = round(microtime(true) * 1000);
    $randomValue = random_int(0, $maxInt);

    $shiftedTimestamp = $timestamp << 22;
    $maskedRandom = $randomValue & $mask22Bits;

    return (string)(($shiftedTimestamp | $maskedRandom) & $maxInt);
}

function extractValue(string $text, string $startStr, string $endStr): string {
    $start = strpos($text, $startStr) + strlen($startStr);
    $end = strpos($text, $endStr, $start);
    return substr($text, $start, $end - $start);
}

function formatResponse(array $response): string {
    $text = "";
    $contents = $response['data']['node']['bot_response_message']['composed_text']['content'] ?? [];
    foreach ($contents as $content) {
        $text .= $content['text'] . "\n";
    }
    return $text;
}

// Main Functionality: Facebook Login
function getFbSession(string $email, string $password, array $proxies = []): array {
    $loginUrl = "https://www.facebook.com/login/?next";

    // Step 1: Initial GET request to fetch login form and tokens
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $loginUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    if (!$response) {
        throw new Exception("Failed to fetch login page.");
    }

    $lsd = extractValue($response, 'name="lsd" value="', '"');
    $jazoest = extractValue($response, 'name="jazoest" value="', '"');

    // Step 2: POST request to submit login credentials
    $postUrl = "https://www.facebook.com/login/?next";
    $postData = http_build_query([
        'lsd' => $lsd,
        'jazoest' => $jazoest,
        'login_source' => 'comet_headerless_login',
        'email' => $email,
        'pass' => $password,
        'login' => '1',
    ]);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $postUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
    curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
    ]);
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    curl_close($ch);

    if (strpos($response, 'login_error') !== false) {
        throw new FacebookInvalidCredentialsException("Invalid credentials or rate-limited.");
    }

    // Step 3: Validate session by checking for cookies
    $cookies = [];
    if (file_exists('cookies.txt')) {
        $cookieFile = file('cookies.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($cookieFile as $line) {
            if ($line[0] === '#' || trim($line) === '') continue;
            $tokens = explode("\t", $line);
            $cookies[$tokens[5]] = $tokens[6] ?? '';
        }
    }

    if (!isset($cookies['sb']) || !isset($cookies['xs'])) {
        throw new FacebookInvalidCredentialsException("Unable to log in. Missing session cookies.");
    }

    return [
        'cookies' => $cookies,
        'response' => $response,
        'headers' => $info,
    ];
}

// Meta Cookie Extraction
function getMetaCookies(): array {
    $url = "https://www.meta.ai/";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return [
        '_js_datr' => extractValue($response, '_js_datr":{"value":"', '",'),
        'abra_csrf' => extractValue($response, 'abra_csrf":{"value":"', '",'),
        'datr' => extractValue($response, 'datr":{"value":"', '",'),
        'lsd' => extractValue($response, '"LSD",[],{"token":"', '"}'),
    ];
}

// Proxy Validation
function getSession(array $proxy = [], string $testUrl = "https://api.ipify.org/?format=json"): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $testUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    if (!empty($proxy)) {
        curl_setopt($ch, CURLOPT_PROXY, $proxy['host']);
        curl_setopt($ch, CURLOPT_PROXYPORT, $proxy['port']);
    }
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception("Proxy not working.");
    }

    return json_decode($response, true);
}

// Test Script
try {
    $session = getFbSession("your_email@example.com", "your_password");
    print_r($session);
} catch (FacebookInvalidCredentialsException $e) {
    echo "Login failed: " . $e->getMessage();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}

?>
