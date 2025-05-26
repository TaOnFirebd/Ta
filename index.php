<?php
// Define models with correct casing
$models = [
    'flux' => 'Flux',
    'turbo' => 'Turbo',
    'midjourney' => 'midjourney',
    'llama' => 'llama',
    'openai' => 'openai',
    'pollination' => 'Pollination',
    'dreamweaver' => 'Dreamweaver'
];

// Define aspect ratios
$ratios = [
    '1:1' => ['width' => 1024, 'height' => 1024],
    '9:16' => ['width' => 768, 'height' => 1366],
    '16:9' => ['width' => 1920, 'height' => 1080],
    '4:3' => ['width' => 1024, 'height' => 768],
    '3:4' => ['width' => 768, 'height' => 1024],
    '2:3' => ['width' => 800, 'height' => 1200],
    '3:2' => ['width' => 1200, 'height' => 800]
];

// Initialize variables
$imageUrl = '';
$error = '';
$generationDetails = [];
$formValues = [
    'prompt' => $_POST['prompt'] ?? '',
    'model' => $_POST['model'] ?? 'flux',
    'ratio' => $_POST['ratio'] ?? '1:1',
    'seed' => $_POST['seed'] ?? '',
    'steps' => $_POST['steps'] ?? 50,
    'guidance' => $_POST['guidance'] ?? 7.5,
    'enhance' => $_POST['enhance'] ?? 'true',
    'safe' => $_POST['safe'] ?? 'false'
];

// Set width/height based on selected ratio
if (isset($ratios[$formValues['ratio']])) {
    $formValues['width'] = $ratios[$formValues['ratio']]['width'];
    $formValues['height'] = $ratios[$formValues['ratio']]['height'];
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate'])) {
    try {
        // Validate inputs
        if (empty($formValues['prompt'])) {
            throw new Exception('Prompt cannot be empty');
        }

        // Get the correctly cased model name
        $apiModel = $models[strtolower($formValues['model'])] ?? 'Flux';
        
        // Prepare API URL based on model
        $encodedPrompt = urlencode($formValues['prompt']);
        
        if ($formValues['model'] === 'pollination') {
            // Pollination API only needs prompt
            $apiUrl = "https://botmaker.serv00.net/pollination.php?prompt={$encodedPrompt}";
        } elseif ($formValues['model'] === 'dreamweaver') {
            // Dreamweaver API only needs prompt
            $apiUrl = "https://botfather.cloud/Apis/ImgGen/client.php?inputText={$encodedPrompt}";
        } else {
            // Other models use full parameters
            $apiUrl = "https://image.hello-kaiiddo.workers.dev/{$encodedPrompt}?" . http_build_query([
                'model' => $apiModel,
                'width' => $formValues['width'],
                'height' => $formValues['height'],
                'steps' => $formValues['steps'],
                'guidance' => $formValues['guidance'],
                'enhance' => $formValues['enhance'],
                'safe' => $formValues['safe']
            ]);
        }

        // Use cURL to fetch the image
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For testing only, remove in production
        curl_setopt($ch, CURLOPT_TIMEOUT, 300); // 5 minutes timeout
        
        $imageData = curl_exec($ch);
        
        if (curl_errno($ch)) {
            throw new Exception('API request failed: ' . curl_error($ch));
        }
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode !== 200) {
            throw new Exception("API returned HTTP status: {$httpCode}");
        }
        
        curl_close($ch);

        // Check if the response is an image
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageData);
        
        if (strpos($mimeType, 'image/') !== 0) {
            throw new Exception('API did not return an image');
        }

        // Convert to data URL for display
        $imageUrl = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
        
        // Prepare generation details
        $generationDetails = [
            'Neural Engine' => $models[$formValues['model']] ?? 'Flux',
            'Prompt' => htmlspecialchars($formValues['prompt']),
            'Aspect Ratio' => $formValues['ratio'],
            'Resolution' => "{$formValues['width']} × {$formValues['height']} px"
        ];

        // Add additional details for non-Pollination/Dreamweaver models
        if (!in_array($formValues['model'], ['pollination', 'dreamweaver'])) {
            $generationDetails = array_merge($generationDetails, [
                'Seed' => $formValues['seed'] ?: 'Random',
                'Iterations' => $formValues['steps'],
                'Creativity Control' => $formValues['guidance'],
                'Quantum Enhance' => $formValues['enhance'] === 'true' ? 'Enabled' : 'Disabled',
                'Safety Protocol' => $formValues['safe'] === 'true' ? 'Enabled' : 'Disabled'
            ]);
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NeonDream - AI Image Generator</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --neon-pink: #ff2a6d;
            --neon-blue: #05d9e8;
            --neon-purple: #d300c5;
            --dark-bg: #0d0221;
            --darker-bg: #070113;
            --card-bg: #170a3a;
            --text-primary: #e0e0e0;
            --text-secondary: #a0a0a0;
            --success: #00ff9d;
            --warning: #ffd700;
            --danger: #ff3864;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Inter', sans-serif;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 20% 30%, rgba(213, 0, 197, 0.15) 0%, transparent 25%),
                radial-gradient(circle at 80% 70%, rgba(5, 217, 232, 0.15) 0%, transparent 25%);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        header {
            text-align: center;
            margin-bottom: 3rem;
            position: relative;
        }

        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(90deg, var(--neon-pink), var(--neon-purple));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .tagline {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 300;
            max-width: 600px;
            margin: 0 auto;
            line-height: 1.6;
        }

        .glow {
            text-shadow: 0 0 10px rgba(255, 42, 109, 0.5);
        }

        .main-card {
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            margin-bottom: 2rem;
        }

        .form-group {
            margin-bottom: 1.8rem;
            position: relative;
        }

        .form-label {
            display: block;
            margin-bottom: 0.8rem;
            font-weight: 500;
            font-size: 0.95rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }

        .form-label .info-icon {
            margin-left: 0.5rem;
            color: var(--neon-blue);
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-label .info-icon:hover {
            color: var(--neon-pink);
        }

        .prompt-input {
            width: 100%;
            padding: 1.2rem;
            background-color: rgba(10, 5, 30, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            line-height: 1.6;
            resize: vertical;
            min-height: 140px;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
        }

        .prompt-input:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 2px rgba(5, 217, 232, 0.2);
        }

        .form-row {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-col {
            flex: 1;
        }

        /* Custom Dropdown Styles */
        .custom-dropdown {
            position: relative;
            width: 100%;
        }

        .dropdown-header {
            padding: 1rem;
            background-color: rgba(10, 5, 30, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
        }

        .dropdown-header:hover {
            border-color: var(--neon-blue);
        }

        .dropdown-header.active {
            border-color: var(--neon-pink);
            border-bottom-left-radius: 0;
            border-bottom-right-radius: 0;
        }

        .dropdown-header .selected-value {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .dropdown-header .model-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .dropdown-header .chevron {
            transition: transform 0.3s ease;
        }

        .dropdown-header.active .chevron {
            transform: rotate(180deg);
        }

        .dropdown-options {
            position: absolute;
            top: 100%;
            left: 0;
            width: 100%;
            background-color: var(--card-bg);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-top: none;
            border-bottom-left-radius: 10px;
            border-bottom-right-radius: 10px;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.3s ease;
            z-index: 100;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .dropdown-options.active {
            max-height: 300px;
            overflow-y: auto;
        }

        .dropdown-option {
            padding: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.2s ease;
        }

        .dropdown-option:hover {
            background-color: rgba(255, 42, 109, 0.1);
        }

        .dropdown-option .model-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.8rem;
            flex-shrink: 0;
        }

        .dropdown-option .model-info {
            flex-grow: 1;
        }

        .dropdown-option .model-name {
            font-weight: 500;
            margin-bottom: 0.2rem;
        }

        .dropdown-option .model-desc {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Model specific colors */
        .model-icon.flux {
            background-color: #8a2be2;
            color: white;
        }
        .model-icon.turbo {
            background-color: #0099ff;
            color: white;
        }
        .model-icon.midjourney {
            background-color: #1a1a2e;
            color: white;
        }
        .model-icon.llama {
            background-color: #ff6b35;
            color: white;
        }
        .model-icon.openai {
            background-color: #10a37f;
            color: white;
        }
        .model-icon.pollination {
            background-color: #7b2dff;
            color: white;
        }
        .model-icon.dreamweaver {
            background-color: #00b4d8;
            color: white;
        }

        /* Input fields */
        .input-field {
            width: 100%;
            padding: 1rem;
            background-color: rgba(10, 5, 30, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-field:focus {
            outline: none;
            border-color: var(--neon-blue);
            box-shadow: 0 0 0 2px rgba(5, 217, 232, 0.2);
        }

        /* Button styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 1.1rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            border: none;
            gap: 0.6rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--neon-pink), var(--neon-purple));
            color: white;
            box-shadow: 0 4px 20px rgba(255, 42, 109, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 25px rgba(255, 42, 109, 0.4);
        }

        .btn-block {
            display: flex;
            width: 100%;
        }

        /* Result container */
        .result-container {
            display: <?= $imageUrl ? 'block' : 'none' ?>;
            background-color: var(--card-bg);
            border-radius: 16px;
            padding: 2.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.05);
            margin-top: 2rem;
        }

        .result-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .result-title {
            font-size: 1.5rem;
            font-weight: 600;
            background: linear-gradient(90deg, var(--neon-pink), var(--neon-blue));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .result-image {
            width: 100%;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            max-height: 70vh;
            object-fit: contain;
            user-select: none;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            pointer-events: none;
        }

        .result-details {
            background-color: rgba(10, 5, 30, 0.5);
            border-radius: 10px;
            padding: 1.5rem;
            font-size: 0.9rem;
            line-height: 1.6;
            border: 1px solid rgba(255, 255, 255, 0.1);
            margin-bottom: 1.5rem;
        }

        .result-details strong {
            color: var(--neon-blue);
            font-weight: 500;
        }

        .download-btn-container {
            text-align: center;
            margin-top: 1.5rem;
        }

        /* Error message */
        .error-message {
            display: <?= $error ? 'block' : 'none' ?>;
            color: var(--danger);
            background-color: rgba(255, 56, 100, 0.1);
            padding: 1rem;
            border-radius: 8px;
            margin-top: 1rem;
            border: 1px solid var(--danger);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 1rem;
            }

            .main-card, .result-container {
                padding: 1.5rem;
            }

            .logo {
                font-size: 2rem;
            }
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltip-text {
            visibility: hidden;
            width: 220px;
            background-color: var(--darker-bg);
            color: var(--text-primary);
            text-align: center;
            border-radius: 6px;
            padding: 0.8rem;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            transform: translateX(-50%);
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.85rem;
            font-weight: normal;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            line-height: 1.5;
        }

        .tooltip .tooltip-text::after {
            content: "";
            position: absolute;
            top: 100%;
            left: 50%;
            margin-left: -5px;
            border-width: 5px;
            border-style: solid;
            border-color: var(--darker-bg) transparent transparent transparent;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Disable form elements during Pollination/Dreamweaver */
        .prompt-only-disabled {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1 class="logo glow">NeonDream</h1>
            <p class="tagline">Generate stunning AI visuals with cybernetic precision. Push the boundaries of digital creativity.</p>
        </header>

        <div class="main-card">
            <form id="imageForm" method="POST">
                <div class="form-group">
                    <label for="prompt">
                        Cyber Prompt
                        <span class="tooltip info-icon">
                            <i class="fas fa-question-circle"></i>
                            <span class="tooltip-text">Describe your vision in detail. Include style, composition, and mood for best results.</span>
                        </span>
                    </label>
                    <textarea id="prompt" name="prompt" class="prompt-input" placeholder="A neon-lit cyberpunk cityscape at night, raining, with holographic advertisements reflecting on wet streets, 4k hyper-detailed, cinematic lighting..." required><?= htmlspecialchars($formValues['prompt']) ?></textarea>
                </div>

                <div class="form-group">
                    <label>
                        Neural Engine
                        <span class="tooltip info-icon">
                            <i class="fas fa-question-circle"></i>
                            <span class="tooltip-text">Select the AI model that best fits your creative needs.</span>
                        </span>
                    </label>
                    <div class="custom-dropdown" id="modelDropdown">
                        <div class="dropdown-header" id="dropdownHeader"
