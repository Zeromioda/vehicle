<?php include 'db.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fleet & Transport Management</title>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
        body {
            background-color: #f4f6f9;
            margin: 0;
            font-family: 'Segoe UI', Arial, sans-serif;
        }

        .bc {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 3;
            background: linear-gradient(90deg, #0d6efd, black);
            color: white;
            padding: 1.5rem 0;
            text-align: center;
            height: 50px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .bc h1 {
            margin: 0;
            width: 100%;
            font-size: 2rem;
            letter-spacing: 1px;
        }
        .bc p {
            margin: 5px 0 0;
        }

        .sidebar {
            width: 240px;
            background: #222f3e;
            color: #fff;
            height: calc(100vh - 50px);
            position: fixed;
            left: 0;
            top: 80px;
            display: flex;
            flex-direction: column;
            box-shadow: 2px 0 8px rgba(0,0,0,0.04);
            z-index: 2;
        }

        .sidebar-header {
            padding: 32px 0 24px 0;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 600;
            background: #222f3e;
            border-bottom: 1px solid #263142;
            letter-spacing: 1px;
        }

        .sidebar-menu {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 4px;
            padding: 18px 0;
        }

        .sidebar-menu a {
            color: #fff;
            text-decoration: none;
            padding: 14px 32px;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            transition: background 0.2s, color 0.2s;
            border-left: 3px solid transparent;
        }

        .sidebar-menu a:hover {
            background: #273c75;
            color: #00aaff;
            border-left: 3px solid #00aaff;
        }

        .sidebar-menu a.active {
            background: #273c75;
            color: #00aaff;
            border-left: 3px solid #00aaff;
        }

        .main-content {
            margin-left: 240px;
            padding: 0;
            min-height: 100vh;
            background: #f4f6f9;
        }

        .iframe-wrapper {
            padding: 40px;
            margin-top: 90px;
        }

        iframe {
            width: 100%;
            height: 650px;
            border: none;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }

        @media (max-width: 768px) {
            .sidebar {
                width: 70px;
                top: 50px;
            }
            .sidebar-header {
                font-size: 1rem;
                padding: 16px 0;
            }
            .sidebar-menu a {
                padding: 10px 10px;
                font-size: 1rem;
            }
            .main-content {
                margin-left: 70px;
            }
            .bc h1 {
                font-size: 1.2rem;
            }
            .iframe-wrapper {
                padding: 10px;
                margin-top: 70px;
            }
            iframe {
                height: 450px;
            }
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
          var sidebarLinks = document.querySelectorAll('.sidebar-menu a');
          sidebarLinks.forEach(function(link) {
            link.addEventListener('click', function() {
              sidebarLinks.forEach(function(l) { l.classList.remove('active'); });
              this.classList.add('active');
            });
          });
        });
    </script>
</head>
<body>
    <div class="bc">
        <h1>Fleet & Transport Management System</h1>
    </div>

    <div class="sidebar">
        <div class="sidebar-menu">
            <a href="dashboard.php" target="contentFrame">
              <h4>Dashboard</h4>
            </a>
            <a href="vehicles.php" target="contentFrame">
              <h4>Manage Vehicles</h4>
            </a>
            <a href="drivers.php" target="contentFrame">
              <h4>Manage Drivers</h4>
            </a>
            <a href="trips.php" target="contentFrame">
                <h4>Dispatch</h4>
            </a>
        </div>
    </div>
    <div class="main-content">
        <div class="iframe-wrapper">
            <iframe name="contentFrame" src="dashboard.php"></iframe>
        </div>
    </div>

    <div id="adminNotificationDisplay" style="
        position: fixed;
        top: 90px; /* Adjust based on your header height */
        right: 20px;
        background-color: #d4edda;
        color: #155724;
        padding: 15px 20px;
        border-radius: 5px;
        border: 1px solid #c3e6cb;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        z-index: 1000;
        display: none; /* Hidden by default */
        max-width: 350px;
        font-size: 0.9em;
    ">
        <span id="notificationMessageText"></span>
        <button style="float: right; background: none; border: none; font-size: 1.2em; cursor: pointer; color: #155724;" onclick="this.parentElement.style.display='none';">&times;</button>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            sidebarLinks.forEach(function(link) {
                link.addEventListener('click', function() {
                    sidebarLinks.forEach(function(l) { l.classList.remove('active'); });
                    this.classList.add('active');
                });
            });

            setInterval(function() {
                $.getJSON('check_admin_notification.php', function(data) {
                    if (data.hasNotification) {
                        alert('Admin Notification: ' + data.message);

                        $('#notificationMessageText').text(data.message);
                        $('#adminNotificationDisplay').fadeIn(500).delay(8000).fadeOut(500); // Show for 8 seconds
                    }
                });
            }, 10000);
        });
    </script>
</body>
</html>