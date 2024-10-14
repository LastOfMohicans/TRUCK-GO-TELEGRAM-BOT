<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @vite(['resources/js/app.js', 'resources/css/app.scss'])
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>TruckGO</title>
</head>
<body>
    <div id="app" class="text-white z-0 position-relative">


        <div class="bg-img-start d-flex flex-column position-relative z-0" style="background-image: url(/img/bg/1.jpg);">
            <nav class="navbar bg-transparent w-100 justify-content-center py-4">
                <div class="col-11">
                    <a href="/"><img src="/img/TruckGO.png" class="nav-icon"></a>
                </div>
            </nav>

            <div class="col-11 col-md-10 col-lg-8 col-xxl-6 mx-auto d-flex flex-column justify-content-center align-items-center text-center message__align">

                <p class="fs-1 fw-bold">Отлично, мы ценим ваш выбор!</p>
                <p class="fs-3">Компания TruckGo выражает Вам свою благодарность, за интерес работы с сервисом. Мы отправим уведомление для Вас, в числе первых о старте работы.</p>

                <a href="/" class="btn btn-primary mt-5 py-3 px-4 fw-bold fs-4 lh-1" role="button">Вернуться на
                    главную</a>
            </div>

            <div class="bg-black-opacity bg-black position-absolute top-0 start-0 w-100 h-100 z-n1"></div>
        </div>
    </div>
</body>
</html>
