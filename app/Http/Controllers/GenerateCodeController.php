<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudstudio\Ollama\Facades\Ollama;

class GenerateCodeController extends Controller
{
    public function generateSql(Request $request)
    {
        $request->validate([
            'sql_file' => 'required|file|mimes:txt,sql|max:2048',
        ]);

        $sqlContent = file_get_contents($request->file('sql_file')->getRealPath());

        return response()->json([
            'sql_content' => $sqlContent,
            'language' => $request->input('language'),
        ]);
    }

    public function generateCode(Request $request)
    {
        $request->validate([
            'sql_content' => 'required|string',
            'language' => 'required|in:javascript,typescript,nextjs,prisma',
        ]);

        $prompt = $this->buildPrompt($request->language, $request->sql_content);

        // Interact with the Ollama agent
        $response = Ollama::agent('API and SQL Code Generator')
            ->prompt($prompt)
            ->model('llama2')
            ->ask();


        // Check if 'response' exists and is valid
        if (isset($response['response'])) {
            return response()->json([
                'generated_code' => $response['response'],  // Return the response string directly
            ]);
        } else {
            // Handle unexpected response format
            return response()->json([
                'error' => 'Unexpected response format',
                'response' => $response  // Return the full response for debugging
            ], 500);
        }
    }


    private function buildPrompt($language, $sqlContent)
    {
        $basePrompt = "Based on the provided SQL schema, generate an API implementation with the following components:

1. API_Route: A descriptive name for the API route (e.g., '/api/users').
2. API_Name: A concise name for the API functionality.
3. Arguments: List of query parameters or request body fields the API accepts. If none, state 'None'.
4. API_Example: Detailed implementation of the API route.

Ensure the generated code adheres to best practices for {$language} and follows a RESTful API design. Include error handling and appropriate HTTP status codes.

For Prisma examples, include the Prisma schema derived from the SQL and use Prisma Client in the API implementation.

Here's the expected response format:

```
Route: API_ROUTE
Name: API_NAME
Arguments: ARGUMENTS
Example:
```{$language}
API_EXAMPLE
```

SQL Schema:
{$sqlContent}
";

        $languageSpecificInstructions = [
            'javascript' => "For JavaScript, use Node.js with Express.js. Include necessary imports and middleware.",
            'typescript' => "For TypeScript, use Node.js with Express.js. Include type definitions for request and response objects.",
            'nextjs' => "For Next.js, use the App Router format. Implement as an API route in the app/api directory. Use Next.js-specific features like NextResponse.",
            'prisma' => "For Prisma with Next.js:
1. Generate a Prisma schema based on the SQL.
2. Include the Prisma schema in the response.
3. Implement the API route using Prisma Client within a Next.js API route (App Router format).
4. Show how to initialize PrismaClient and use it in the API implementation."
        ];

        return $basePrompt . "\n\n" . $languageSpecificInstructions[$language];
    }
}
