<!DOCTYPE html>
<html>
<head>
    <!-- Your head content here -->
</head>
<body>
    <div class="container">
        <!-- Check if a success message is present in the session and display it -->
        @if(session('success_message'))
            <div class="alert alert-success">
                {{ session('success_message') }}
            </div>
        @endif

        <!-- Check if an error message is present in the session and display it -->
        @if(session('error_message'))
            <div class="alert alert-danger">
                {{ session('error_message') }}
            </div>
        @endif

        <!-- Your success page content here -->
        <h1 style="color: green;">Transaction is Successful</h1>
        <!-- Additional content for your success page -->
        <div class="row">
            <div class="col-2"><a href="/">homepage</a> </div>
            <div class="col-2"><a href="/login">Login</a> </div>

        </div>
    </div>
</body>
</html>
