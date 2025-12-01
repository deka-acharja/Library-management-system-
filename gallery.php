<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Library Gallery</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="app.css">
    <style>
        .main-section {
            margin-top: 0px;
            margin-bottom: 10px;
            background: url('images/background.jpg') no-repeat center center/cover;
            height: 95%;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }

        .gallery-section {
            background-color: rgba(55, 78, 63, 0.7);
            padding: 40px 20px;
            width: 100%;
            box-sizing: border-box;
        }

        .gallery-title {
            font-size: 36px;
            margin-bottom: 20px;
            color: #fff;
            text-align: center;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            padding: 0;
            margin: 0 auto;
            max-width: 1000px;
        }

        .gallery-item {
            text-align: center;
            border: 2px solid rgb(19, 46, 14);
            border-radius: 10px;
            padding: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .gallery-item:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
        }

        .image-caption {
            margin-top: 8px;
            font-size: 12px;
            color: #fdfdfd;
        }

        .gallery-item img {
            width: 40%;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(255, 255, 255, 0.1);
            transition: transform 0.3s ease;
        }

        @media (max-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 480px) {
            .gallery-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Include Header -->
    <?php include('includes/header.php'); ?>

    <div class="main-section">
        <div class="gallery-section">
            <h2 class="gallery-title">Gallery</h2>

            <?php
            // Array of image names (genres)
            $imageNames = [
                'Drama',
                'Adventure Fiction',
                'Autobiography',
                'Biography',
                'Classics',
                'Contemporary Literature',
                'Fairy Tale',
                'Fantasy',
                'Graphic Novels',
                'Historical Fiction',
                'History',
                'Horror',
                'Literary Fiction',
                'Mystery',
                'Non-Fiction',
                'Poetry',
                'Romance Novels',
                'Satire',
                'Science Fiction',
                'Self-help Book',
                'Short Story',
                'Thriller',
                'Western Fiction',
                'Young Adult'
            ];
            ?>

            <div class="gallery-grid">
                <?php foreach ($imageNames as $index => $name): ?>
                    <?php
                    $imagePath = "images/gallery" . ($index + 1) . ".jpg";
                    if (!file_exists($imagePath)) {
                        $imagePath = "images/default.jpg"; // fallback
                    }
                    ?>
                    <div class="gallery-item">
                        <a href="gallery_book_des.php?genre=<?php echo urlencode($name); ?>">
                            <img src="<?php echo $imagePath; ?>" alt="<?php echo $name; ?>">
                            <p class="image-caption"><?php echo $name; ?></p>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Include Footer -->
    <?php include('includes/footer.php'); ?>
</body>

</html>
