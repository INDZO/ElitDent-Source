<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1) KORISNIK (sa Role umesto ADMINISTRATOR tabele)
        Schema::create('KORISNIK', function (Blueprint $table) {
            $table->string('Email', 120)->primary();
            $table->string('Lozinka', 255);
            $table->string('Ime', 30);
            $table->string('Prezime', 40);
            $table->enum('Role', ['admin', 'pacijent', 'doktor'])->default('pacijent');
        });

        // 2) PACIJENT (PK=Email, FK->KORISNIK.Email)
        Schema::create('PACIJENT', function (Blueprint $table) {
            $table->string('Email', 120)->primary();
            $table->date('DatumRodjenja');
            $table->string('Adresa', 120);
            $table->string('Telefon', 20);

            $table->foreign('Email')
                ->references('Email')->on('KORISNIK')
                ->onDelete('cascade');
        });

        // 3) DOKTOR (PK=Email, FK->KORISNIK.Email)
        Schema::create('DOKTOR', function (Blueprint $table) {
            $table->string('Email', 120)->primary();
            $table->string('Specijalizacija', 50);
            $table->string('Telefon', 20);

            $table->foreign('Email')
                ->references('Email')->on('KORISNIK')
                ->onDelete('cascade');
        });

        // 4) ZDRAVSTVENI_PODACI (PK=PacijentEmail, FK->PACIJENT.Email)
        Schema::create('ZDRAVSTVENI_PODACI', function (Blueprint $table) {
            $table->string('PacijentEmail', 120)->primary();
            $table->string('Alergije', 255)->nullable();
            $table->string('HronicneBolesti', 255)->nullable();
            $table->string('Terapije', 30)->nullable();
            $table->string('Napomena', 40)->nullable();
            $table->date('DatumAzuriranja');

            $table->foreign('PacijentEmail')
                ->references('Email')->on('PACIJENT')
                ->onDelete('cascade');
        });

        // 5) TIM (PK=TimID, FK VodjaEmail -> DOKTOR.Email)
        Schema::create('TIM', function (Blueprint $table) {
            $table->bigIncrements('TimID');
            $table->string('Naziv', 80)->unique();
            $table->string('VodjaEmail', 120);
            $table->string('Opis', 255);

            $table->foreign('VodjaEmail')
                ->references('Email')->on('DOKTOR')
                ->onDelete('restrict');
        });

        // 6) TIM_CLAN (PK=(TimID, DoktorEmail), FK->TIM, FK->DOKTOR)
        Schema::create('TIM_CLAN', function (Blueprint $table) {
            $table->unsignedBigInteger('TimID');
            $table->string('DoktorEmail', 120);

            $table->primary(['TimID', 'DoktorEmail']);

            $table->foreign('TimID')
                ->references('TimID')->on('TIM')
                ->onDelete('cascade');

            $table->foreign('DoktorEmail')
                ->references('Email')->on('DOKTOR')
                ->onDelete('cascade');
        });

        // 7) DOKTOR_RASPOLOZIVOST (PK=DoktorEmail, FK->DOKTOR)
        Schema::create('DOKTOR_RASPOLOZIVOST', function (Blueprint $table) {
            $table->string('DoktorEmail', 120)->primary();
            $table->string('RadniDan', 10);
            $table->time('VremeOd');
            $table->time('VremeDo');

            $table->foreign('DoktorEmail')
                ->references('Email')->on('DOKTOR')
                ->onDelete('cascade');
        });

        // 8) DOKTOR_NERADNI_DAN (PK=(DoktorEmail, Datum), FK->DOKTOR)
        Schema::create('DOKTOR_NERADNI_DAN', function (Blueprint $table) {
            $table->string('DoktorEmail', 120);
            $table->date('Datum');
            $table->string('Razlog', 120)->nullable();

            $table->primary(['DoktorEmail', 'Datum']);

            $table->foreign('DoktorEmail')
                ->references('Email')->on('DOKTOR')
                ->onDelete('cascade');
        });

        // 9) KATALOG_USLUGA (PK=Naziv)
        Schema::create('KATALOG_USLUGA', function (Blueprint $table) {
            $table->string('Naziv', 80)->primary();
            $table->string('Opis', 255);
            $table->unsignedInteger('TrajanjeMinuta');
            $table->decimal('Cena', 10, 2);
        });

        // 10) TIM_NUDI_USLUGU (PK=(TimID, NazivUsluge), FK->TIM, FK->KATALOG_USLUGA)
        Schema::create('TIM_NUDI_USLUGU', function (Blueprint $table) {
            $table->unsignedBigInteger('TimID');
            $table->string('NazivUsluge', 80);

            $table->primary(['TimID', 'NazivUsluge']);

            $table->foreign('TimID')
                ->references('TimID')->on('TIM')
                ->onDelete('cascade');

            $table->foreign('NazivUsluge')
                ->references('Naziv')->on('KATALOG_USLUGA')
                ->onDelete('cascade');
        });

        // 11) TERMIN (PK=TerminID, FK->PACIJENT, FK->TIM, FK->KATALOG_USLUGA)
        Schema::create('TERMIN', function (Blueprint $table) {
            $table->bigIncrements('TerminID');

            $table->date('Datum');
            $table->time('VremePocetka');
            $table->time('VremeZavrsetka');

            $table->string('Status', 15); // Zakazan / Realizovan / Otkazan
            $table->date('DatumOtkazivanja')->nullable();
            $table->string('Razlog', 200)->nullable();

            $table->string('PacijentEmail', 120);
            $table->unsignedBigInteger('TimID');
            $table->string('NazivUsluge', 80);

            $table->foreign('PacijentEmail')
                ->references('Email')->on('PACIJENT')
                ->onDelete('restrict');

            $table->foreign('TimID')
                ->references('TimID')->on('TIM')
                ->onDelete('restrict');

            $table->foreign('NazivUsluge')
                ->references('Naziv')->on('KATALOG_USLUGA')
                ->onDelete('restrict');
        });

        // 12) STOMATOLOSKI_KARTON (PK=PacijentEmail, FK->PACIJENT)
        Schema::create('STOMATOLOSKI_KARTON', function (Blueprint $table) {
            $table->string('PacijentEmail', 120)->primary();

            $table->string('PlanTerapije', 255)->nullable();
            $table->string('Preporuke', 255)->nullable();
            $table->string('Beleske', 255)->nullable();
            $table->string('Napomena', 255)->nullable();

            $table->foreign('PacijentEmail')
                ->references('Email')->on('PACIJENT')
                ->onDelete('cascade');
        });

        // 13) DENTALNA_SEMA (PK=(PacijentEmail, Zub), FK->PACIJENT)
        Schema::create('DENTALNA_SEMA', function (Blueprint $table) {
            $table->string('PacijentEmail', 120);
            $table->string('Zub', 5);
            $table->string('Stanje', 80);
            $table->string('Napomena', 120)->nullable();

            $table->primary(['PacijentEmail', 'Zub']);

            $table->foreign('PacijentEmail')
                ->references('Email')->on('PACIJENT')
                ->onDelete('cascade');
        });

        // 14) INTERVENCIJA (PK=IntervencijaID, FK->TERMIN, FK->PACIJENT, FK->DOKTOR)
        Schema::create('INTERVENCIJA', function (Blueprint $table) {
            $table->bigIncrements('IntervencijaID');

            $table->unsignedBigInteger('TerminID');
            $table->string('PacijentEmail', 120);
            $table->string('DoktorEmail', 120);

            $table->string('Zub', 5)->nullable();
            $table->string('Opis', 255);
            $table->string('Napomena', 120)->nullable();

            $table->foreign('TerminID')
                ->references('TerminID')->on('TERMIN')
                ->onDelete('cascade');

            $table->foreign('PacijentEmail')
                ->references('Email')->on('PACIJENT')
                ->onDelete('restrict');

            $table->foreign('DoktorEmail')
                ->references('Email')->on('DOKTOR')
                ->onDelete('restrict');
        });

        // 15) DOKUMENT (PK=(PacijentEmail, Putanja), FK->PACIJENT)
        Schema::create('DOKUMENT', function (Blueprint $table) {
            $table->string('PacijentEmail', 120);
            $table->string('Putanja', 255);

            $table->string('Naziv', 120);
            $table->string('Tip', 15);
            $table->date('DatumDodavanja');
            $table->string('Napomena', 120)->nullable();

            $table->primary(['PacijentEmail', 'Putanja']);

            $table->foreign('PacijentEmail')
                ->references('Email')->on('PACIJENT')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('DOKUMENT');
        Schema::dropIfExists('INTERVENCIJA');
        Schema::dropIfExists('DENTALNA_SEMA');
        Schema::dropIfExists('STOMATOLOSKI_KARTON');
        Schema::dropIfExists('TERMIN');
        Schema::dropIfExists('TIM_NUDI_USLUGU');
        Schema::dropIfExists('KATALOG_USLUGA');
        Schema::dropIfExists('DOKTOR_NERADNI_DAN');
        Schema::dropIfExists('DOKTOR_RASPOLOZIVOST');
        Schema::dropIfExists('TIM_CLAN');
        Schema::dropIfExists('TIM');
        Schema::dropIfExists('ZDRAVSTVENI_PODACI');
        Schema::dropIfExists('DOKTOR');
        Schema::dropIfExists('PACIJENT');
        Schema::dropIfExists('KORISNIK');
    }
};
