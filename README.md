MetaAI API Wrapper
MetaAI is a Python library designed to interact with Meta's AI APIs that run in the backend of https://www.meta.ai/. It encapsulates the complexities of authentication and communication with the APIs, providing a straightforward interface for sending queries and receiving responses.

With this you can easily prompt the AI with a message and get a response, directly from your Python code. NO API KEY REQUIRED

Meta AI is connected to the internet, so you will be able to get the latest real-time responses from the AI. (powered by Bing)

Meta AI is running Llama 3 LLM.

Features
Prompt AI: Send a message to the AI and get a response from Llama 3.
Image Generation: Generate images using the AI. (Only for FB authenticated users)
Get Up To Date Information: Get the latest information from the AI thanks to its connection to the internet.
Get Sources: Get the sources of the information provided by the AI.
Streaming: Stream the AI's response in real-time or get the final response.
Follow Conversations: Start a new conversation or follow up on an existing one.
Usage
php metaAi.php

PHP Extensions: Your PHP installation has the necessary extensions like cURL enabled.
Local Server (if needed): You can also place this script in a local server environment like XAMPP, WAMP, or Laravel Homestead.

Replace "your_email@example.com" and "your_password" with your actual Facebook login credentials for testing.
If Facebook’s security mechanisms detect bot activity, the script might not work.
