<?php
/**
 * 404 Error Page
 * Shown when a page or module is not found
 */

// Page title
$pageTitle = '404 - Page Not Found';

// Check if header is already included
if (!defined('SITE_NAME')) {
    require_once 'includes/config.php';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - <?php echo SITE_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #0a0e27;
        }

        .block404 {
            position: relative;
            background-image: url(https://imgur.com/eHVyWTM.jpg);
            background-size: cover;
            background-position: center;
            width: 100vw;
            height: 100vh;
            overflow: hidden;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .waves {
            position: absolute;
            width: 100%;
            height: 100%;
            background-image: url(https://imgur.com/eHVyWTM.jpg);
            background-size: cover;
            background-position: center;
            filter: url("#glitch");
            z-index: 1;
        }

        .t404 {
            position: absolute;
            width: 364px;
            height: 146px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-image: url(https://imgur.com/KPZo9YX.png);
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            z-index: 3;
        }

        .obj {
            width: 204px;
            height: 209px;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            animation: animation-404 6s infinite ease-in-out;
            z-index: 2;
        }

        .obj img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        @keyframes animation-404 {
            0%,
            100% {
                transform: translate(-50%, -50%) rotate(0);
            }
            50% {
                transform: translate(-53%, -42%) rotate(-5deg);
            }
        }

        .content-404 {
            position: absolute;
            bottom: 150px;
            z-index: 10;
            text-align: center;
            color: white;
            max-width: 700px;
        }

        .error-title {
            font-size: 42px;
            font-weight: 700;
            margin: 30px 0 20px;
            text-shadow: 0 3px 15px rgba(0,0,0,0.5);
        }

        .error-message {
            font-size: 20px;
            margin: 20px 0 40px;
            line-height: 1.6;
            opacity: 0.95;
            text-shadow: 0 2px 10px rgba(0,0,0,0.5);
        }

        .button-group {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 30px;
        }

        .btn-404 {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 40px;
            background: rgba(255,255,255,0.9);
            color: #0a0e27;
            text-decoration: none;
            border-radius: 50px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            border: none;
        }

        .btn-404:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.4);
            background: white;
            color: #0a0e27;
        }

        .btn-404.btn-outline {
            background: transparent;
            border: 3px solid white;
            color: white;
        }

        .btn-404.btn-outline:hover {
            background: white;
            color: #0a0e27;
        }


        .module-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        .module-link {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 20px;
            background: rgba(255,255,255,0.1);
            color: white;
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.3s ease;
            font-size: 15px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .module-link:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.3);
            border-color: rgba(255,255,255,0.3);
        }

        .module-link i {
            font-size: 18px;
        }

        @media (max-width: 768px) {
            .t404 {
                width: 280px;
                height: 112px;
            }
            
            .obj {
                width: 160px;
                height: 164px;
            }
            
            .error-title {
                font-size: 32px;
            }
            
            .error-message {
                font-size: 16px;
            }
            
            .btn-404 {
                padding: 12px 28px;
                font-size: 14px;
            }
            
            .module-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .module-link {
                padding: 12px 16px;
                font-size: 13px;
            }

            .content-404 {
                bottom: 180px;
                padding: 0 20px;
            }
        }

        @media (max-width: 480px) {
            .t404 {
                width: 220px;
                height: 88px;
            }
            
            .obj {
                width: 120px;
                height: 123px;
            }
            
            .error-title {
                font-size: 24px;
            }

            .error-message {
                font-size: 14px;
            }

            .module-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="block404">
        <div class="waves"></div>
        
        <div class="obj">
            <img src="https://imgur.com/w0Yb4MX.png" alt="404 Character">
        </div>
        
        <div class="t404"></div>

        <svg xmlns="http://www.w3.org/2000/svg" version="1.1" style="position: absolute; width: 0; height: 0;">
            <defs>
                <filter id="glitch">
                    <feTurbulence type="fractalNoise" baseFrequency="0.01 0.03" numOctaves="1" result="warp" id="turb"/>
                    <feColorMatrix in="warp" result="huedturb" type="hueRotate" values="90">
                        <animate attributeType="XML" attributeName="values" values="0;180;360" dur="3s" repeatCount="indefinite"/>
                    </feColorMatrix>
                    <feDisplacementMap xChannelSelector="R" yChannelSelector="G" scale="50" in="SourceGraphic" in2="huedturb"/>
                </filter>
            </defs>
        </svg>
        
        <div class="content-404">
            
            <div class="button-group">
                <a href="<?php echo BASE_URL; ?>/" class="btn-404">
                    <i class="fas fa-home"></i> Go Home
                </a>
                <a href="javascript:history.back()" class="btn-404 btn-outline">
                    <i class="fas fa-arrow-left"></i> Go Back
                </a>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
