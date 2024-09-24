<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SQL to API Generator</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.16/dist/tailwind.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/notyf@3.10.0/notyf.min.css" />
    <style>
        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 100;
            padding: 1rem 1.5rem;
            background-color: #4a5568;
            color: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            0% {
                opacity: 0;
                transform: translateY(20px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-blue-500 mb-6">SQL to API Generator</h1>
        <div class="bg-white shadow-md rounded-md p-6 mb-6">
            <div class="flex items-center justify-between mb-4">
                <label for="language-select" class="font-medium">Language:</label>
                <select id="language-select" class="border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="javascript">JavaScript</option>
                    <option value="laravel">Laravel</option>
                    <option value="nextjs">Next.js</option>
                    <option value="typescript">TypeScript</option>
                </select>
            </div>
            <form id="sqlForm" enctype="multipart/form-data" class="mb-6">
                <label for="sqlFile" class="block mb-2 font-medium">Choose SQL File</label>
                <div class="flex items-center justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md cursor-pointer" id="file-upload-area">
                    <div class="text-center">
                        <i class="fas fa-file-upload text-gray-400 text-4xl mb-2"></i>
                        <p class="text-gray-500 font-medium">Drag and drop your SQL file here or click to select</p>
                        <input type="file" id="sqlFile" name="sql_file" accept=".sql, .txt" required class="hidden">
                    </div>
                </div>
                <div id="file-info" class="mt-2 hidden">
                    <p>File: <span id="file-name"></span></p>
                </div>
                <div class="flex justify-center mt-4">
                    <button type="submit" id="generateBtn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Generate API
                    </button>
                </div>
            </form>
            <div id="loading" class="text-center text-blue-500 text-lg hidden">
                <div class="lds-ring">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
                Loading SQL...
            </div>
            <h2 class="text-2xl font-medium mb-4">SQL Content:</h2>
            <pre id="sqlContent" class="language-sql bg-gray-100 rounded-md p-4 mb-6 overflow-auto"></pre>
            <div id="ollamaLoading" class="text-center text-blue-500 text-lg hidden">
                <div class="lds-ring">
                    <div></div>
                    <div></div>
                    <div></div>
                    <div></div>
                </div>
                Generating API Code...
            </div>
            <h2 class="text-2xl font-medium mb-4">Generated Code:</h2>
            <div class="bg-blue-100 rounded-md p-4 mb-6">
                <pre id="generatedCode" class="language-javascript"></pre>
            </div>
            <div class="flex justify-center">
                <button id="exportCode" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                    Export Code
                </button>
            </div>
        </div>
    </div>

    <div id="exampleModal" class="fixed z-10 inset-0 overflow-y-auto hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-xl p-6">
                <h2 class="text-2xl font-bold mb-4">Example SQL File</h2>
                <pre class="language-sql bg-gray-100 rounded-md p-4 mb-4 overflow-auto">
CREATE TABLE users (
    id INT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (id, name, email) VALUES
    (1, 'John Doe', 'john@example.com'),
    (2, 'Jane Smith', 'jane@example.com'),
    (3, 'Bob Johnson', 'bob@example.com');
                </pre>
                <div class="flex justify-end">
                    <button id="closeModal" class="bg-gray-500 text-white px-4 py-2 rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="toast hidden" id="toast">
        <div class="toast-content">
            <i class="fas fa-check-circle text-green-500 mr-2"></i>
            <span id="toast-message"></span>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/notyf@3.10.0/notyf.min.js"></script>
    <script>
        let selectedLanguage = 'javascript';
        let controller; // For storing AbortController
        const notyf = new Notyf({
            duration: 3000,
            position: {
                x: 'right',
                y: 'bottom',
            },
            types: [
                {
                    type: 'success',
                    background: 'green',
                    icon: {
                        className: 'fas fa-check-circle',
                        tagName: 'i',
                        color: '#fff'
                    }
                },
                {
                    type: 'error',
                    background: 'red',
                    icon: {
                        className: 'fas fa-exclamation-circle',
                        tagName: 'i',
                        color: '#fff'
                    }
                }
            ]
        });

        const languageSelect = document.getElementById('language-select');
        languageSelect.addEventListener('change', (event) => {
            selectedLanguage = event.target.value;
        });

        const fileUploadArea = document.getElementById('file-upload-area');
        fileUploadArea.addEventListener('click', () => {
            document.getElementById('sqlFile').click();
        });

        document.getElementById('sqlFile').addEventListener('change', (event) => {
            const file = event.target.files[0];
            if (file) {
                document.getElementById('file-info').classList.remove('hidden');
                document.getElementById('file-name').textContent = file.name;
            }
        });

        document.getElementById('sqlForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            formData.append('language', selectedLanguage);
            controller = new AbortController(); // Create a new AbortController
            const signal = controller.signal;

            document.getElementById('generateBtn').disabled = true;
            document.getElementById('loading').classList.remove('hidden');

            fetch('/generate-sql', {
                    method: 'POST',
                    body: formData,
                    signal: signal // Add signal to the fetch request
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    document.getElementById('sqlContent').textContent = data.sql_content; // Display SQL content
                    Prism.highlightElement(document.getElementById('sqlContent')); // Syntax highlighting
                    document.getElementById('generatedCode').textContent = ''; // Clear previous generated code
                    document.getElementById('ollamaLoading').classList.remove('hidden'); // Show loading indicator for Ollama

                    fetch('/generate-code', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                sql_content: data.sql_content,
                                language: data.language // Use the language returned from the server
                            }),
                            signal: signal // Add signal to the fetch request
                        })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok ' + response.statusText);
                            }
                            return response.json();
                        })
                        .then(data => {
                            // Display the generated code from Ollama
                            const generatedCode = data.generated_code.response;
                            document.getElementById('generatedCode').textContent = generatedCode;
                            Prism.highlightElement(document.getElementById('generatedCode')); // Syntax highlighting
                            notyf.success('API code generated successfully!');
                        })
                        .catch(error => {
                            if (error.name === 'AbortError') {
                                console.log('Request canceled');
                            } else {
                                console.error('Error:', error);
                                notyf.error('An error occurred: ' + error.message);
                            }
                        })
                        .finally(() => {
                            document.getElementById('ollamaLoading').classList.add('hidden'); // Hide loading indicator for Ollama
                            document.getElementById('loading').classList.add('hidden');
                            document.getElementById('generateBtn').disabled = false;
                        });
                })
                .catch(error => {
                    if (error.name === 'AbortError') {
                        console.log('Request canceled');
                    } else {
                        console.error('Error:', error);
                        notyf.error('An error occurred: ' + error.message);
                    }
                });
        });

        const exportCodeBtn = document.getElementById('exportCode');
        exportCodeBtn.addEventListener('click', () => {
            // Implement code export functionality
            const generatedCode = document.getElementById('generatedCode').textContent;
            const codeBlob = new Blob([generatedCode], { type: 'text/plain' });
            const url = URL.createObjectURL(codeBlob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'generated-code.txt';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            notyf.success('Code exported successfully!');
        });

        const exampleModal = document.getElementById('exampleModal');
        const openModalBtn = document.createElement('button');
        openModalBtn.classList.add('bg-blue-500', 'text-white', 'px-4', 'py-2', 'rounded-md', 'hover:bg-blue-600', 'focus:outline-none', 'focus:ring-2', 'focus:ring-blue-500', 'focus:ring-offset-2');
        openModalBtn.textContent = 'Show Example SQL File';
        openModalBtn.addEventListener('click', () => {
            exampleModal.classList.remove('hidden');
        });

        const closeModalBtn = document.getElementById('closeModal');
        closeModalBtn.addEventListener('click', () => {
            exampleModal.classList.add('hidden');
        });

        // Add the example SQL button to the DOM
        document.querySelector('.container').appendChild(openModalBtn);
    </script>

    <style>
        .lds-ring {
            display: inline-block;
            position: relative;
            width: 24px;
            height: 24px;
            margin-right: 8px;
        }
        .lds-ring div {
            box-sizing: border-box;
            display: block;
            position: absolute;
            width: 20px;
            height: 20px;
            margin: 2px;
            border: 2px solid #4299e1;
            border-radius: 50%;
            animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
            border-color: #4299e1 transparent transparent transparent;
        }
        .lds-ring div:nth-child(1) {
            animation-delay: -0.45s;
        }
        .lds-ring div:nth-child(2) {
            animation-delay: -0.3s;
        }
        .lds-ring div:nth-child(3) {
            animation-delay: -0.15s;
        }
        @keyframes lds-ring {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }
    </style>
</body>

</html>
