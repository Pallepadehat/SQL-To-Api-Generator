<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Cloudstudio\Ollama\Facades\Ollama;
use Illuminate\Support\Facades\Storage;

class SQLGenerationController extends Controller
{
    public function generateSQL(Request $request)
    {
        // Validate the uploaded file
        $request->validate([
            'sql_file' => 'required|file|mimes:sql,txt|max:2048', // Accept .sql or .txt files
        ]);

        // Store the uploaded file temporarily
        $path = $request->file('sql_file')->store('temp');

        // Read the content of the file
        $sqlInput = Storage::get($path);

        // Return the SQL content and the selected language
        return response()->json([
            'sql_content' => $sqlInput,
            'language' => $request->input('language', 'javascript'),
        ]);
    }

    public function generateCode(Request $request)
    {
        // Validate the SQL content and language selection received
        $request->validate([
            'sql_content' => 'required|string', // Ensure SQL content is provided
            'language' => 'required|in:javascript,laravel,nextjs,typescript', // Validate language selection
        ]);

        // Use Ollama AI to generate API route, arguments, and code based on the selected language
        $response = Ollama::agent('API and SQL Code Generator')
            ->prompt("Based on the provided SQL schema, return the information specified below for the {$request->language} language.

            The expected variables and their descriptions are:
            1. **API_Route_Name**: A name for the API route (e.g., '/api/users').
            2. **API_Name**: The descriptive name for the generated API route based on the SQL schema.
            3. **Arguments**: A list of arguments that the API route will accept. If none, state 'None'.
            4. **Api_Example**: Provide an example API route implementation in the {$request->language} language, following the guidelines below:

            - For JavaScript or TypeScript, use the Next.js app router format:
            ```javascript
            import { NextResponse } from 'next/server';
            export async function GET(request: Request) {
              // Implement your API logic here
              const items = [
                { id: 1, name: 'Item 1' },
                { id: 2, name: 'Item 2' },
              ];
              return NextResponse.json(items, { status: 200 });
            }
            ```

            - For Laravel, provide a route and controller example that retrieves data from the provided SQL schema:
            ```php
            Route::get('/api/users', [UserController::class, 'index']);

            class UserController extends Controller
            {
                public function index()
                {
                    users = DB::table('users')->get();
                    return response()->json(users);
                }
            }
            ```

            The expected response format is and only return the **Api_Example** in {$request->language}:
            ```
            Route:
            **API_Route_NAME**
            Api_Name:
            **API_NAME**
            Arguments:
            **ARGUMENTS**
            Api_Example:
            ```{$request->language}
            **API_EXAMPLE**
            ```
            Here is the SQL schema:
            {$request->sql_content}")
            ->model('llama2')
            ->ask();

        return response()->json([
            'generated_code' => $response,
        ]);
    }
}
