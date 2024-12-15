<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
    <title>Secure Image Watermarking</title>
</head>

<body>
    <div class="container">
        <h1>Secure Image Watermarking</h1>

        <div class="section">
            <h2>Embed Secret Message with Watermark</h2>
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="image">Select Image:</label>
                    <input type="file" name="image" id="image" accept="image/png,image/jpeg" required>
                </div>

                <div class="form-group">
                    <label for="message">Secret Message:</label>
                    <textarea name="message" id="message" rows="4" required></textarea>
                </div>

                <div class="form-group">
                    <label for="key">Encryption Key:</label>
                    <input type="password" name="key" id="key" required>
                </div>


                <button type="submit" name="action" value="embed" class="btn">Embed Message</button>
            </form>
        </div>

        <div class="section">
            <h2>Extract Secret Message</h2>
            <form action="process.php" method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="watermarked-image">Select Watermarked Image:</label>
                    <input type="file" name="image" id="watermarked-image" accept="image/png,image/jpeg" required>
                </div>

                <div class="form-group">
                    <label for="decrypt-key">Decryption Key:</label>
                    <input type="password" name="key" id="decrypt-key" required>
                </div>

                <button type="submit" name="action" value="extract" class="btn">Extract Message</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('watermark-type').addEventListener('change', function () {
            const textSection = document.getElementById('text-watermark-section');
            const logoSection = document.getElementById('logo-watermark-section');

            switch (this.value) {
                case 'text':
                    textSection.style.display = 'block';
                    logoSection.style.display = 'none';
                    break;
                case 'logo':
                    textSection.style.display = 'none';
                    logoSection.style.display = 'block';
                    break;
                default:
                    textSection.style.display = 'none';
                    logoSection.style.display = 'none';
            }
        });
    </script>
</body>

</html>