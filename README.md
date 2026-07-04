NOVI README

# E-bankarstvo — REST API aplikacija

Backend REST API aplikacija za e-bankarstvo, razvijena u okviru predmeta **Serverske veb tehnologije** (2025/26). Aplikacija omogućava korisnicima upravljanje bankovnim računima, prenos sredstava (uključujući devizne račune sa konverzijom po dnevnom kursu), pregled i pretragu transakcija, i administraciju korisnika sa tri nivoa pristupa.

## Tim

- Ivana Jovanović 2023/0415
- Đorđe Jovanović 2023/0412
- Uroš Jevtić 2023/0586

## Tehnologije

- **PHP 8.3+**
- **Laravel 13**
- **Laravel Sanctum** — token-bazirana autentifikacija (Bearer token)
- **SQLite** — baza podataka (razvojno okruženje)
- **Eloquent ORM** — rad sa bazom, migracije, relacije
- Javni servisi: **open.er-api.com** i **Frankfurter API** (api.frankfurter.dev) — devizni kursevi i lista podržanih valuta
- **Postman** — testiranje API-ja

## Funkcionalnosti

- Registracija, prijava, odjava i reset zaboravljene lozinke (Sanctum tokeni)
- Tri korisničke uloge: **client**, **manager**, **admin**, sa različitim ovlašćenjima
- Kreiranje i pregled bankovnih računa (dinarski i devizni — RSD, EUR, USD)
- Prenos sredstava između računa, uz automatsku konverziju valuta po dnevnom kursu
- Pregled, pretraga, filtriranje i sortiranje transakcija (sa paginacijom)
- Statistika potrošnje po kategoriji (SQL JOIN + agregacija)
- Keširanje podataka sa javnih servisa (devizni kursevi, lista valuta)
- Administracija korisnika (promjena uloge, blokiranje/odblokiravanje naloga)
- Zaštita od IDOR-a, SQL injekcije i neovlašćenog pristupa (uloge + middleware)

## Pokretanje projekta lokalno

### Preduslovi

- PHP 8.3 ili noviji
- Composer
- Git

### Koraci

1. **Kloniranje repozitorijuma**

   ```bash
   git clone https://github.com/elab-development/serverske-veb-tehnologije-2025-26-ebankapp_2023_0412.git
   cd serverske-veb-tehnologije-2025-26-ebankapp_2023_0412
   ```

2. **Instaliranje zavisnosti**

   ```bash
   composer install
   ```

3. **Kopiranje env. fajla**

   ```bash
   cp .env.example .env
   php artisan key:generate
   ```


4. **Kreiranje SQLite baze i pokretanje migracija**

   ```bash
   type nul > database\database.sqlite   
   

   php artisan migrate
   ```

5. **Pokretanje razvojnog servera**

   ```bash
   php artisan serve
   ```

   Aplikacija je dostupna na `http://127.0.0.1:8000`.

6. **Testiranje API-ja**

   Aplikacija nema frontend — sve rute se testiraju preko alata **Postman** (ili sličnog HTTP klijenta). Provjeri da je server pokrenut, pa pošalji zahtjeve na rute navedene ispod.

## Pregled API ruta (skraćeno)

| Metoda | Ruta | Pristup | Opis |
|---|---|---|---|
| POST | `/api/register` | Javna | Registracija korisnika |
| POST | `/api/login` | Javna | Prijava, vraća Bearer token |
| POST | `/api/logout` | Client+ | Odjava, poništava token |
| GET | `/api/me` | Client+ | Podaci o ulogovanom korisniku |
| POST | `/api/forgot-password` | Javna | Zahtjev za reset lozinke |
| POST | `/api/reset-password` | Javna | Reset lozinke tokenom |
| GET / POST | `/api/accounts` | Client+ | Lista / kreiranje računa |
| POST | `/api/transfer` | Client+ | Prenos sredstava sa konverzijom valuta |
| GET | `/api/transactions` | Client+ | Lista transakcija (paginacija, filtriranje, sortiranje) |
| GET | `/api/transactions/search` | Client+ | Pretraga transakcija |
| GET | `/api/transactions/spending-by-category` | Client+ | Statistika potrošnje po kategoriji |
| GET | `/api/currencies/rates/{base?}` | Javna | Dnevna kursna lista |
| GET | `/api/currencies/supported` | Javna | Lista podržanih valuta |
| GET | `/api/users` | Admin | Lista korisnika |
| PATCH | `/api/users/{id}/role` | Admin | Promjena uloge |
| PATCH | `/api/users/{id}/block` | Admin | Blokiranje korisnika |

Potpuna specifikacija svih ruta (headeri, parametri, formati odgovora) nalazi se u pratećoj dokumentaciji seminarskog rada.

## Autentifikacija

Aplikacija koristi **Bearer token** autentifikaciju (Laravel Sanctum). Nakon uspješne prijave (`POST /api/login`), server vraća token koji se prosleđuje u `Authorization` header-u svakog narednog zahtjeva:

```
Authorization: Bearer {token}
```

## Uloge korisnika

| Uloga | Ovlašćenja |
|---|---|
| **client** | Upravlja sopstvenim računima i transakcijama |
| **manager** | Dodatno: izmjena i brisanje računa/transakcija |
| **admin** | Dodatno: upravljanje korisnicima (uloge, blokiranje) |

## Napomena

Ovaj projekat je izrađen kao seminarski rad u okviru predmeta Serverske veb tehnologije, Elektrotehnički fakultet. Kod je javno dostupan isključivo u obrazovne svrhe.
