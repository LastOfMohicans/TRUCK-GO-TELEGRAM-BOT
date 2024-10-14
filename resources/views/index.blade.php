<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @vite(['resources/js/app.js', 'resources/css/app.scss'])
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>TruckGO</title>
</head>
<body>
    <div id="app" class="text-white">
        <div class="bg-img-start d-flex position-relative z-0" style="background-image: url(/img/bg/1.jpg);">
            <div class="col-11 mx-auto d-flex flex-column align-items-center flex-lg-row position-relative z-2">
                <div class="d-flex flex-column align-items-center mt-5 text-center align-items-lg-start col-12 col-lg-7 text-lg-start text__block">
                    <img src="/img/TruckGO.png" class="logo-icon">
                    <hr class="border border-2 opacity-100 w-75 horizontal-line my-4">
                    <p class="fs-2 fs-lg-1 lh-sm">Первый в России <br>агрегатор поставщиков <br>нерудных и строительных материалов.</p>
                    <p class="fs-5 fs-lg-4">В любом месте - по лучшей цене с быстрой доставкой.</p>
                </div>

                <div class="d-flex flex-column align-items-center mt-5 mb-5 align-self-lg-end col-12 col-lg-5 ms-auto">
                    <p class="text-center fs-5 fs-lg-4 mb-4 lh-sm text-lg-start col-12 col-md-10 col-lg-12">Сервис
                        находится в процессе доработки и оптимизации, старт работы запланирован на ноябрь 2024г.<br>Пока,
                        вы можете оставить заявку на работу с сервисом:</p>
                    <div class="d-grid gap-2 w-100 d-md-flex justify-content-md-center justify-content-lg-start">
                        <button class="btn btn-primary btn-main py-2 px-4 fw-bold fs-4 lh-1 w-100" type="button" data-bs-toggle="modal" data-bs-target="#clientModal">Хочу заказывать <br>материалы</button>
                        <button class="btn btn-secondary btn-main py-2 px-4 fw-bold fs-4 lh-1 w-100" type="button" data-bs-toggle="modal" data-bs-target="#driverModal">Хочу стать <br>поставщиком</button>
                    </div>
                </div>
            </div>

            <div class="gradient-bottom position-absolute bottom-0 start-0 w-100 h-100 z-n1 d-none d-lg-block"></div>
            <div class="gradient-side position-absolute bottom-0 start-0 w-100 h-100 z-n1 d-none d-lg-block"></div>
            <div class="bg-black-opacity bg-black position-absolute top-0 start-0 w-100 h-100 z-n1 d-lg-none"></div>
        </div>

        <client-form></client-form>
        <driver-form></driver-form>
    </div>
</body>
</html>
