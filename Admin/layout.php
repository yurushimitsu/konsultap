<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Admin Dashboard</title>
  
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">

  <style>
 
  </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
      
        <nav class="navbar navbar-expand-md navbar-dark col-md-2 "  style="background-color: rgba(0, 0, 102, 0.3);">
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#sidebarCollapse" aria-controls="sidebarCollapse" aria-expanded="false" aria-label="Toggle Sidebar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse px-" id="sidebarCollapse">
                <div class="sidebar-sticky d-md-flex flex-column justify-content-between vh-100 ">
             
                    <div>
                        <img src="../images/logoadmin.png" class="mt-4 pb-3 border-bottom">
                        <div class="row">
                            <div class="col-md-6">
                                <img src="../images/" class="mt-4 pb-3" alt="User Image"> 
                            </div>
                               <!-- Dito ung sa sidebar panel diba marami nakalagay sa medical practioner part dito ka magdadagdag -->
                            <div class="col-md-6">
                                <p class="mt-4 pb-3">Admin name</p> 
                            </div>
                            <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="#" style="color: black;">
                                <i class="bi bi-list" style="color: black;"></i>
                                Manage User <span class="sr-only">(current)</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#" style="color: black;"> 
                                <i class="bi bi-upload" style="color: black;"></i>
                                Upload Data
                            </a>
                        </li>
                    </ul>
                        </div>
                    </div>
                
                   
                
                    <div>
                        <ul class="nav flex-column mt-auto">
                            <li class="nav-item">
                                <a class="nav-link" href="#" style="color: black;">
                                    <i class="bi bi-box-arrow-right" style="color: black;"></i>
                                    Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>
        
      
        <main role="main" class="col-md-10">
                <!-- Dito mo ilalagay ung content -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>
          
            <div class="row">
                <div class="col-md-12">
                    
                </div>
            </div>
        </main>
    </div>
</div>


<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
