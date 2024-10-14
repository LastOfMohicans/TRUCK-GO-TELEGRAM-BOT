<script setup>
    import { reactive } from 'vue'
    
    var userData = reactive({
      client_name: "",
      phone: "",
      email: "",
      inn: "",
    })
</script>

<template>
    <div class="modal fade" id="driverModal" tabindex="-1" aria-labelledby="driverModalLabel" aria-hidden="true">
        <div class="modal-dialog text-dark">
            <div class="modal-content">
                <div class="d-flex justify-content-end p-2">
                    <button type="button" class="btn-close p-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body d-flex justify-content-center">


                    <div class="container col-11 col-md-10">
                        <form class="need-validation" action="/sendform">
                            <div class="row flex-column g-3">
                                <div class="my-3">
                                    <label for="nameInput" class="form-label fs-5">Имя</label>
                                    <input type="text" class="form-control fs-4" id="nameInput" name="name" autocomplete="off" v-model="userData.client_name">
                                </div>

                                <div class="my-3">
                                    <label for="phoneInput" class="form-label fs-5">Номер телефона<span class="d-inline-block asterisk-icon ms-1">*</span></label>
                                    <input type="tel" class="form-control fs-4" id="phoneInput" name="phone" pattern="^(?:\+7\s?|8\s?)\(?\d{3}\)?(?:[\-\s]?\d\d\d)(?:[\-\s]?\d\d){2}$" placeholder="+7 (999) 999-99-99" required autocomplete="on" v-model="userData.phone">

                                    <div class="invalid-feedback">
                                        Это поле обязательно для заполнения.
                                    </div>
                                </div>

                                <div class="my-3">
                                    <label for="emailInput" class="form-label fs-5">Электронная почта<span class="asterisk-icon ms-1">*</span></label>
                                    <input type="email" class="form-control fs-4" id="emailInput" name="email" required autocomplete="email" placeholder="example@mail.com" v-model="userData.email">

                                    <div class="invalid-feedback">
                                        Это поле обязательно для заполнения.
                                    </div>
                                </div>

                                <div class="my-3">
                                    <label for="innInput" class="form-label fs-5">ИНН организации или ИП</label>
                                    <input type="text" class="form-control fs-4" id="innInput" name="inn" autocomplete="off" v-model="userData.inn">
                                </div>

                                <button class="btn btn-primary btn-lg mt-4 mb-5 fs-4 no-border" type="submit">Оставить заявĸу</button>
                            </div>
                        </form>
                    </div>


                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>

</style>