<?php
include 'encrypt.php';
include 'proseswatermark.php';  // Include the WatermarkProcessor class

class ImageSteganography {
    // Improved Embedding method
    public static function embedMessage($sourceImagePath, $outputImagePath, $encryptedMessage, $watermarkText = null, $watermarkOptions = []) {
        // Validate input file
        if (!file_exists($sourceImagePath)) {
            throw new Exception("Source image does not exist.");
        }

        // Get image info
        $imageInfo = getimagesize($sourceImagePath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file.");
        }
        $mimeType = $imageInfo['mime'];

        // Create image resource
        switch ($mimeType) {
            case 'image/png':
                $image = imagecreatefrompng($sourceImagePath);
                break;
            case 'image/jpeg':
                $image = imagecreatefromjpeg($sourceImagePath);
                break;
            default:
                throw new Exception("Unsupported image format. Only PNG and JPEG are allowed.");
        }

        if (!$image) {
            throw new Exception("Failed to create image resource.");
        }

        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Check image capacity
        $maxMessageLength = floor(($width * $height * 3) / 8);
        
        // Convert message to binary with length prefix
        $messageBinary = str_pad(decbin(strlen($encryptedMessage)), 32, '0', STR_PAD_LEFT);
        foreach (str_split($encryptedMessage) as $char) {
            $messageBinary .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT);
        }

        // Check message length
        if (strlen($messageBinary) > $maxMessageLength) {
            throw new Exception("Message is too large for this image. Max capacity: " . $maxMessageLength . " bits");
        }

        // Embed message
        $bitIndex = 0;
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                if ($bitIndex >= strlen($messageBinary)) break 2;

                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;

                // Embed message in blue channel's least significant bit
                $bitValue = $messageBinary[$bitIndex] === '1' ? 1 : 0;
                $b = ($b & 0xFE) | $bitValue;
                $bitIndex++;

                $newColor = imagecolorallocate($image, $r, $g, $b);
                imagesetpixel($image, $x, $y, $newColor);
            }
        }

        // Ensure output directory exists
        $outputDir = dirname($outputImagePath);
        if (!is_dir($outputDir)) {
            mkdir($outputDir, 0777, true);
        }

        // Save image
        switch ($mimeType) {
            case 'image/png':
                imagepng($image, $outputImagePath);
                break;
            case 'image/jpeg':
                imagejpeg($image, $outputImagePath, 100);  // Highest quality
                break;
        }

        imagedestroy($image);
        return true;
    }

    // Improved Extraction method
    public static function extractMessage($watermarkedImagePath) {
        // Validate input file
        if (!file_exists($watermarkedImagePath)) {
            throw new Exception("Watermarked image does not exist.");
        }

        // Get image info
        $imageInfo = getimagesize($watermarkedImagePath);
        if (!$imageInfo) {
            throw new Exception("Invalid image file.");
        }
        $mimeType = $imageInfo['mime'];

        // Create image resource
        switch ($mimeType) {
            case 'image/png':
                $image = imagecreatefrompng($watermarkedImagePath);
                break;
            case 'image/jpeg':
                $image = imagecreatefromjpeg($watermarkedImagePath);
                break;
            default:
                throw new Exception("Unsupported image format. Only PNG and JPEG are allowed.");
        }

        if (!$image) {
            throw new Exception("Failed to open the image.");
        }

        // Get image dimensions
        $width = imagesx($image);
        $height = imagesy($image);

        // Extract message bits
        $messageBits = '';
        $extractedLength = 0;
        $lengthExtracted = false;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $b = $rgb & 0xFF; // Blue channel
                
                if (!$lengthExtracted) {
                    // First 32 bits represent message length
                    $messageBits .= $b & 1;
                    if (strlen($messageBits) == 32) {
                        $extractedLength = bindec($messageBits);
                        $messageBits = '';
                        $lengthExtracted = true;
                    }
                } else {
                    // Extract message bits
                    $messageBits .= $b & 1;
                    
                    // Stop when we've extracted enough bits for the message
                    if (strlen($messageBits) == ($extractedLength * 8)) {
                        break 2;
                    }
                }
            }
        }

        // Convert bits to characters
        $extractedMessage = '';
        for ($i = 0; $i < strlen($messageBits); $i += 8) {
            $byte = substr($messageBits, $i, 8);
            if (strlen($byte) == 8) {
                $extractedMessage .= chr(bindec($byte));
            }
        }

        imagedestroy($image);
        return $extractedMessage;
    }
}

// Main processing logic
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $action = filter_input(INPUT_POST, 'action', FILTER_SANITIZE_STRING);
        $key = filter_input(INPUT_POST, 'key', FILTER_SANITIZE_STRING);

        // Validate action
        if (!in_array($action, ['embed', 'extract'])) {
            throw new Exception("Invalid action.");
        }

        // Validate uploaded file
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed.");
        }

        // Determine upload directory
        $uploadDir = $action === 'embed' ? 'images/original/' : 'images/watermarked/';
        
        // Ensure upload directory exists
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        // Sanitize filename and generate safe filename
        $originalFileName = basename($_FILES['image']['name']);
        $safeFileName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $originalFileName);
        $uploadedFile = $uploadDir . $safeFileName;

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadedFile)) {
            if ($action === 'embed') {
                // Validate and sanitize message
                $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
                if (empty($message)) {
                    throw new Exception("Message cannot be empty.");
                }

                // Encrypt message
                $encryptedMessage = SecureEncryption::encrypt($message, $key);
                $watermarkedFile = 'images/watermarked/' . $safeFileName;
                
                // Handle watermarking
                $watermarkType = filter_input(INPUT_POST, 'watermark_type', FILTER_SANITIZE_STRING);
                
                // First embed the hidden message
                ImageSteganography::embedMessage($uploadedFile, $watermarkedFile, $encryptedMessage);

                // Apply watermarking if specified
                switch ($watermarkType) {
                    case 'text':
                        $watermarkText = filter_input(INPUT_POST, 'watermark_text', FILTER_SANITIZE_STRING);
                        $watermarkPosition = filter_input(INPUT_POST, 'watermark_text_position', FILTER_SANITIZE_STRING) ?? 'bottom-right';
                        
                        WatermarkProcessor::addTextWatermark(
                            $watermarkedFile, 
                            $watermarkedFile, 
                            $watermarkText, 
                            [
                                'position' => $watermarkPosition,
                                'opacity' => 0.5
                            ]
                        );
                        break;

                    case 'logo':
                        // Handle logo upload
                        if (!empty($_FILES['logo_image']['name'])) {
                            $logoUploadDir = 'images/logos/';
                            if (!is_dir($logoUploadDir)) {
                                mkdir($logoUploadDir, 0777, true);
                            }
                            
                            // Sanitize logo filename
                            $logoOriginalName = basename($_FILES['logo_image']['name']);
                            $logoSafeName = preg_replace('/[^a-zA-Z0-9_.-]/', '', $logoOriginalName);
                            $logoFile = $logoUploadDir . $logoSafeName;
                            
                            // Move logo file
                            if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $logoFile)) {
                                $logoPosition = filter_input(INPUT_POST, 'watermark_logo_position', FILTER_SANITIZE_STRING) ?? 'bottom-right';
                                
                                WatermarkProcessor::addLogoWatermark(
                                    $watermarkedFile, 
                                    $watermarkedFile, 
                                    $logoFile, 
                                    [
                                        'position' => $logoPosition,
                                        'opacity' => 0.5
                                    ]
                                );
                            } else {
                                throw new Exception("Failed to upload logo.");
                            }
                        }
                        break;
                }

                echo "Message embedded successfully! Check 'watermarked/' folder.";
            } elseif ($action === 'extract') {
                $extractedMessage = ImageSteganography::extractMessage($uploadedFile);
                $decryptedMessage = SecureEncryption::decrypt($extractedMessage, $key);
                echo "Extracted Message: " . htmlspecialchars($decryptedMessage);
            }
        } else {
            throw new Exception("Failed to upload file.");
        }
    } catch (Exception $e) {
        // Log the full error for debugging
        error_log($e->getMessage());
        echo "Error: " . htmlspecialchars($e->getMessage());
    }
}
?>