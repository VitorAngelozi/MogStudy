<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
    <h1>Login</h1>
    <form action="/loginsubmit" method="post">
        @csrf
         <input type="email" name="email"><br><br>
        <label>Senha</label><br>
        <input type="password" name="password"><br><br>
        <button type="submit">Entrar</button>
    </form>
    
    {{-- errors --}}
    @if($errors->any())
        <h1>
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </h1>
    @endif
</body>
</html>