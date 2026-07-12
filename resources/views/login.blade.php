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
         <input type="email" name="email" value="{{old('email')}}" ><br><br>
         @error('email')
            <h1>{{ $message }}</h1>
         @enderror
        <label>Senha</label><br>
        <input type="password" name="password"><br><br>
        @error('password')
            <h1>{{ $message }}</h1>
         @enderror
        <button type="submit">Entrar</button>
    </form>
    
</body>
</html>