<?php // Include any necessary PHP files like database connection, etc. // include('includes/db.php'); // Example for database connection if needed. ?>  
<!DOCTYPE html> 
<html lang="en">  
<head>     
    <meta charset="UTF-8">     
    <meta name="viewport" content="width=device-width, initial-scale=1.0">     
    <title>Library Management System</title>     
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">     
    <link rel="stylesheet" href="app.css"> <!-- Replace with your actual CSS file path --> 
    <style>
        /* Main hero section */
        .hero-section {
            position: relative;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.7)), url('images/background.jpg') no-repeat center center/cover;
            height: 60vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            padding: 0 2rem;
            font-family: Cambria, Cochin, Georgia, Times, 'Times New Roman', serif;
        }
        
        .hero-content h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .hero-content p {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            max-width: 700px;
            line-height: 1.6;
        }
        
        .search-container {
            width: 100%;
            max-width: 600px;
            margin-top: 2rem;
        }
        
        .search-box {
            display: flex;
            width: 100%;
        }
        
        .search-input {
            flex: 1;
            padding: 15px;
            font-size: 1rem;
            border: none;
            border-radius: 5px 0 0 5px;
        }
        
        .search-button {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 0 20px;
            border-radius: 0 5px 5px 0;
            cursor: pointer;
            font-size: 1rem;
        }
        
        /* Features section */
        .features-section {
            padding: 4rem 2rem;
            background-color: #f9f9f9;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2rem;
            color: #333;
        }
        
        .features-container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            width: 100%;
            max-width: 350px;
            text-align: center;
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: #e74c3c;
            margin-bottom: 1rem;
        }
        
        .feature-title {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .feature-desc {
            color: #666;
            line-height: 1.6;
        }
        
        /* Collections section */
        .collections-section {
            padding: 4rem 2rem;
            background-color: white;
        }
        
        .collections-container {
            display: flex;
            overflow-x: auto;
            gap: 1.5rem;
            padding: 1rem 0;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .collection-card {
            min-width: 250px;
            background: #f5f5f5;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .collection-img {
            height: 180px;
            background-color: #ddd;
            background-position: center;
            background-size: cover;
        }
        
        .collection-info {
            padding: 1.5rem;
        }
        
        .collection-title {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .collection-count {
            color: #666;
            font-size: 0.9rem;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(to right, #2c3e50, #4ca1af);
            color: white;
            padding: 4rem 2rem;
            text-align: center;
        }
        
        .cta-content {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .cta-title {
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        
        .cta-desc {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        
        .cta-buttons {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 30px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background-color: #e74c3c;
            color: white;
            border: 2px solid #e74c3c;
        }
        
        .btn-secondary {
            background-color: transparent;
            border: 2px solid white;
            color: white;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2rem;
            }
            
            .hero-content p {
                font-size: 1.2rem;
            }
            
            .features-container {
                gap: 1.5rem;
            }
            
            .cta-title {
                font-size: 2rem;
            }
        }
    </style>
</head>  

<body>      
    <?php include('includes/header.php'); ?>
    
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Welcome to <span style="color: #e74c3c;">Library Zone</span></h1>
            <p>Discover a world of knowledge, stories, and ideas in our extensive collection of books and resources.</p>
            
            <div class="search-container">
                <form action="index.php" method="GET" class="search-box">
                    <input type="text" name="query" placeholder="Search books, authors, or categories..." class="search-input">
                    <button type="submit" class="search-button">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
        </div>
    </section>
    
    
    <?php include('includes/footer.php'); ?>
</body>  
</html>