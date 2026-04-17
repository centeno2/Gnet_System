<?php

use Livewire\Component;

new class extends Component {
    //
};
?>

<div class="min-h-screen bg-[#F0F3F7] p-6">
    <x-card class="relative overflow-hidden rounded-3xl border border-[#D7E4F3] bg-white shadow-sm">
        <div class="absolute -top-10 -right-10 h-40 w-40 rounded-full bg-[#EAF2FB] opacity-80"></div>
        <div class="absolute -bottom-8 -left-8 h-28 w-28 rounded-full bg-[#D7EAFB] opacity-70"></div>
        <div class="absolute top-1/2 right-20 h-6 w-6 rounded-full bg-[#BFD9F6]"></div>
        <div class="absolute top-10 left-1/3 h-4 w-4 rounded-full bg-[#D7EAFB]"></div>

        <div class="relative flex flex-col items-center justify-center py-14">
            <div class="mb-6 rounded-4xl border border-[#D7E4F3] bg-linear-to-br from-[#F8FBFF] to-[#EAF2FB] p-6 shadow-md">
                <div class="flex h-64 w-64 items-center justify-center rounded-full bg-white shadow-sm md:h-72 md:w-72">
                    <img
                        src="{{ asset('img/gnetlogo.png') }}"
                        alt="Logo GNET"
                        class="h-48 w-48 object-contain md:h-56 md:w-56"
                    />
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-3">
                <span class="h-3 w-3 rounded-full bg-[#0E48A1]"></span>
                <span class="h-3 w-3 rounded-full bg-[#2E8BC0]"></span>
                <span class="h-3 w-3 rounded-full bg-[#BFD9F6]"></span>
            </div>
        </div>
    </x-card>
</div>