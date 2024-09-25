<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL to API Generator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-okaidia.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <style>
        .code-container {
            max-height: 500px;
            overflow-y: auto;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-bold text-blue-600 mb-8 text-center animate-bounce">SQL to API Generator</h1>
        <div class="bg-white shadow-lg rounded-lg p-6 mb-8">
            <form id="sqlForm" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="language-select" class="block text-lg font-medium text-gray-700 mb-2">Select Language/Framework:</label>
                    <select id="language-select" name="language" class="w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                        <option value="javascript">JavaScript (Node.js + Express)</option>
                        <option value="typescript">TypeScript (Node.js + Express)</option>
                        <option value="prisma">Prisma + Next.js (Typescript & App Router)</option>
                    </select>
                </div>
                <div>
                    <label for="sqlFile" class="block text-lg font-medium text-gray-700 mb-2">Upload SQL File:</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                        <div class="space-y-1 text-center">
                            <i class="fas fa-file-upload text-gray-400 text-4xl mb-3"></i>
                            <div class="flex text-sm text-gray-600">
                                <label for="sqlFile" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload a file</span>
                                    <input id="sqlFile" name="sql_file" type="file" class="sr-only" accept=".sql,.txt">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">SQL or TXT up to 2MB</p>
                        </div>
                    </div>
                </div>
                <div id="file-info" class="hidden">
                    <p class="text-sm text-gray-600">Selected file: <span id="file-name" class="font-semibold"></span></p>
                </div>
                <div class="flex justify-center">
                    <button type="submit" id="generateBtn" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Generate API
                    </button>
                </div>
            </form>
        </div>

        <div id="results" class="hidden space-y-8">
            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">SQL Content:</h2>
                <div class="code-container">
                    <pre><code id="sqlContent" class="language-sql"></code></pre>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-800 mb-4">Generated API Code:</h2>

                <div class="code-container">
                    <pre><code id="generatedCode" class="language-javascript"></code></pre>
                </div>
            </div>

            <div class="flex justify-center space-x-4">
                <button id="exportCode" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-download mr-2"></i> Export Code
                </button>
                <button id="copyCode" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <i class="fas fa-copy mr-2"></i> Copy Code
                </button>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-typescript.min.js"></script>

    <script>
        const sqlForm = document.getElementById('sqlForm');
        const fileInput = document.getElementById('sqlFile');
        const fileInfo = document.getElementById('file-info');
        const fileName = document.getElementById('file-name');
        const generateBtn = document.getElementById('generateBtn');
        const results = document.getElementById('results');
        const sqlContent = document.getElementById('sqlContent');
        const generatedCode = document.getElementById('generatedCode');
        const exportCodeBtn = document.getElementById('exportCode');
        const copyCodeBtn = document.getElementById('copyCode');

        fileInput.addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                fileInfo.classList.remove('hidden');
                fileName.textContent = file.name;
            }
        });

        sqlForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(sqlForm);

            try {
                generateBtn.disabled = true;
                generateBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Generating...';

                // Fetch the SQL content
                const sqlResponse = await fetch('/generate-sql', {
                    method: 'POST',
                    body: formData
                });

                if (!sqlResponse.ok) {
                    throw new Error('Failed to generate SQL content.');
                }

                const sqlData = await sqlResponse.json();
                console.log('SQL Data:', sqlData);

                // Display SQL Content
                sqlContent.textContent = sqlData.sql_content;
                Prism.highlightElement(sqlContent);

                // Fetch the generated API code
                const codeResponse = await fetch('/generate-code', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        sql_content: sqlData.sql_content,
                        language: sqlData.language
                    })
                });

                if (!codeResponse.ok) {
                    const errorData = await codeResponse.json();
                    throw new Error(errorData.error || 'Failed to generate API code.');
                }

                const codeData = await codeResponse.json();
                console.log('Code Data:', codeData);

                // Ensure generated_code is a string
                if (!codeData.generated_code) {
                    throw new Error('No generated code received.');
                }

                // Parse the generated_code
                const responseLines = codeData.generated_code.split('\n');
                const routeLine = responseLines.find(line => line.startsWith('Route:'));
                const nameLine = responseLines.find(line => line.startsWith('Name:'));
                const argumentsLine = responseLines.find(line => line.startsWith('Arguments:'));

                // Extract code block
                const codeStartIndex = responseLines.findIndex(line => line.startsWith('```')) + 1;
                const codeEndIndex = responseLines.lastIndexOf('```');
                const extractedCode = responseLines.slice(codeStartIndex, codeEndIndex).join('\n');

                // Display Generated Code
                generatedCode.textContent = extractedCode;
                Prism.highlightElement(generatedCode);

                // Show the results section
                results.classList.remove('hidden');
            } catch (error) {
                console.error('Error:', error);
                alert('An error occurred while generating the API. Please try again.\n' + error.message);
            } finally {
                generateBtn.disabled = false;
                generateBtn.innerHTML = 'Generate API';
            }
        });

        exportCodeBtn.addEventListener('click', () => {
            const code = generatedCode.textContent;
            if (!code) {
                alert('No code to export.');
                return;
            }

            const blob = new Blob([code], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'generated_api.txt';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        });

        copyCodeBtn.addEventListener('click', () => {
            const code = generatedCode.textContent;
            if (!code) {
                alert('No code to copy.');
                return;
            }

            navigator.clipboard.writeText(code).then(() => {
                alert('Code copied to clipboard!');
            }).catch(err => {
                console.error('Error copying code: ', err);
                alert('Failed to copy code.');
            });
        });
    </script>
</body>

</html>
