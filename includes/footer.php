<!DOCTYPE html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>RIM Footer Design</title>
        <link rel="stylesheet" href="app.css">

        <style>
            /* Reset styles */
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
            }

            /* Footer styling */
            footer {
                background-color:rgb(193, 206, 192);
                color: rgb(12, 12, 12);
                padding: 30px 0 0 0;
                position: relative;
                width: 100%;
            }

            .footer-container {
                display: flex;
                justify-content: space-between;
                padding: 0 30px;
                flex-wrap: wrap;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            }

            .footer-column {
                flex: 1;
                margin-right: 30px;
                min-width: 200px;
            }

            .footer-column h3 {
                margin-bottom: 15px;
                font-size: 1rem;
                color: #000;
                font-weight: bold;
            }

            .footer-column ul {
                list-style: none;
                margin-bottom: 20px;
            }

            .footer-column ul li {
                margin-bottom: 5px;
            }

            .footer-column ul li a {
                color: #504d4d;
                margin-bottom: 8px;
                display: block;
                transition: color 0.3s;
                text-decoration: none;
                font-size: 14px;
            }

            .footer-column ul li a:hover {
                color: #ffc107;
            }

            .footer-column p {
                margin-bottom: 10px;
                font-size: 14px;
            }

            .footer-bottom {
                background-color: #fafafa;
                text-align: center;
                padding: 10px 0;
                margin-top: 20px;
                width: 100%;
            }

            .footer-bottom p {
                font-size: 0.9rem;
                margin: 0;
                color: #207c0d;
            }

            /* Responsive Styles */
            @media (max-width: 768px) {
                .footer-container {
                    flex-direction: column;
                }

                .footer-column {
                    width: 100%;
                    margin-right: 0;
                    margin-bottom: 20px;
                }
            }
        </style>
    </head>

    <body>
        <!-- Footer Section -->
        <footer>
            <div class="footer-container">
                <div class="footer-column">
                    <h3>Library Links</h3>
                    <ul>
                        <li><a href="https://powerlibrary.org/catalog/">Online PA Catalogue-Library</a></li>
                        <li><a href="#">RIM Digital Repository-Library</a></li>
                        <li><a href="#">Electronic Resources-Library</a></li>
                        <li><a href="#">Scopus-Database</a></li>
                        <li><a href="#">DELNET DATABASE</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Internal Services</h3>
                    <ul>
                        <li><a href="http://moodle.rim.edu.bt/">Courseware Moodle</a></li>
                        <li><a href="http://rimlms.rim.edu.bt/">RIM LMS</a></li>
                        <li><a href="#">RIM Mail</a></li>
                        <li><a href="https://www.rim.edu.bt/">RIM Service Requisition System</a></li>
                        <li><a href="#">FRIENDS</a></li>
                        <li><a href="https://www.rim.edu.bt/">RIM Sitemap</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Useful Links</h3>
                    <ul>
                        <li><a href="https://tech.gov.bt/">GovTech Bhutan</a></li>
                        <li><a href="https://btcirt.bt/">Bhutan Computer Incident Response Team</a></li>
                        <li><a href="https://scs.rbp.gov.bt/">Citizen Security Clearance Portal</a></li>
                        <li><a href="https://www.citizenservices.gov.bt/g2cportal/ListOfLifeEventComponent">Citizen Services Portal</a></li>
                    </ul>
                </div>

                <div class="footer-column">
                    <h3>Royal Institute of Management</h3>
                    <p>Post Box No 416<br>
                        Simtokha, Thimphu (11001)<br>
                        BHUTAN</p>
                    <p>Telephone: +975 2 351013/14</p>
                </div>
            </div>

            <div class="footer-bottom">
                <p>&copy; 2025 Royal Institute of Management | Designed by DICT, RIM</p>
            </div>
        </footer>
    </body>

    </html>