<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>

    <h3>Hello, {{$user['username']}} </h3>  
    <h3>Email: {{ $user['email'] }}</h3>
   
    <h4>write your note<h4>

        <form action="/notepost" method="POST">
            @csrf

            <textarea name="content" placeholder="Write your note"></textarea>
            <button type="submit">Submit</button>
        
        </form>
        
    <hr>



    <form action="/logout" method="POST">
        @csrf
        <button type="submit">Logout</button>
    </form>
    
</body>
</html>