# E-Banking Web Aplikacija

Laravel REST API aplikacija za e-bankarstvo. Omogućava korisnicima pregled stanja na računu, uvid u transakcije, prenos sredstava između računa uz podršku za devizne račune i dnevni kurs, kao i pretragu troškova po nazivu i kategorijama.

---

## Tehnologije

- **PHP 8.5** / **Laravel 11**
- **SQLite** (baza podataka)
- **Laravel Sanctum** (autentifikacija tokenom)
- **Postman** (testiranje API-ja)

---

## Pokretanje aplikacije

```bash
# 1. Kloniranje repozitorijuma
git clone https://github.com/elab-development/serverske-veb-tehnologije-2025-26-ebankapp_2023_0412
cd serverske-veb-tehnologije-2025-26-ebankapp_2023_0412

# 2. Instalacija zavisnosti
composer install

# 3. Kopiranje .env fajla
cp .env.example .env
php artisan key:generate

# 4. Pokretanje migracija
php artisan migrate:fresh

# 5. Pokretanje servera
php artisan serve
```

---

## Modeli

### User
Predstavlja korisnika sistema. Ima ulogu (`admin`, `manager`, `client`) i može posjedovati više računa.

### Account
Predstavlja bankovni račun korisnika. Može biti dinarski ili devizni (RSD, EUR, USD). Ima stanje (`balance`), status (`active`, `frozen`, `closed`) i IBAN broj.

### Transaction
Predstavlja transakciju između dva računa. Čuva podatke o pošiljaocu, primaocu, iznosu, valuti, kategoriji i opisu.

### PasswordReset
Pomoćni model za reset lozinke. Čuva email, token i vrijeme isteka.

---

## Enumi

| Enum | Vrijednosti |
|---|---|
| `Role` | admin, manager, client |
| `AccType` | dinarski, devizni |
| `AccStatus` | active, frozen, closed |
| `Currency` | RSD, EUR, USD |

---

## Migracije

| Migracija | Tip |
|---|---|
| `create_users_table` | Kreiranje tabele |
| `create_accounts_table` | Kreiranje tabele |
| `create_transactions_table` | Kreiranje tabele |
| `modify_transactions_table` | Izmjena postojeće kolone |
| `change_balance_account` | Izmjena kolone (preciznost) |
| `add_to_account` | Dodavanje kolone (iban) |
| `remove_from_account` | Brisanje kolone |
| `add_column_transaction_table` | Dodavanje kolone |
| `remove_column_transaction_table` | Brisanje kolone |
| `add_profile_fields_to_users_table` | Dodavanje kolona (phone, jmbg, address, is_active) |
| `change_name_length_in_users_table` | Izmjena dužine kolone |
| `add_unique_constraints_to_users_table` | Postavljanje ograničenja |
| `create_password_reset_tokens` | Kreiranje tabele |
| `create_personal_access_tokens_table` | Kreiranje tabele (Sanctum) |

---

## Uloge i pristup rutama

| Akcija | Neulogovan | Client | Manager | Admin |
|---|---|---|---|---|
| register / login | ✅ | ✅ | ✅ | ✅ |
| Pregled svojih računa | ❌ | ✅ | ✅ | ✅ |
| Kreiranje računa | ❌ | ✅ | ✅ | ✅ |
| Transfer sredstava | ❌ | ✅ | ✅ | ✅ |
| Pretraga transakcija | ❌ | ✅ | ✅ | ✅ |
| Izmjena/brisanje računa | ❌ | ❌ | ✅ | ✅ |
| Upravljanje korisnicima | ❌ | ❌ | ❌ | ✅ |
| Blokiranje korisnika | ❌ | ❌ | ❌ | ✅ |
| Promjena uloge | ❌ | ❌ | ❌ | ✅ |

---

## API Rute

### Javne rute (bez autentifikacije)

| Metoda | Ruta | Opis |
|---|---|---|
| GET | `/api/status` | Provjera statusa API-ja |
| POST | `/api/register` | Registracija korisnika |
| POST | `/api/login` | Prijava korisnika |
| POST | `/api/forgot-password` | Zahtjev za reset lozinke |
| POST | `/api/reset-password` | Reset lozinke tokenom |

### Zaštićene rute — Client (auth:sanctum)

| Metoda | Ruta | Opis |
|---|---|---|
| GET | `/api/me` | Podaci o ulogovanom korisniku |
| POST | `/api/logout` | Odjava |
| GET | `/api/accounts` | Lista računa (paginacija + filtriranje) |
| POST | `/api/accounts` | Kreiranje računa |
| GET | `/api/accounts/{id}` | Detalji računa |
| GET | `/api/accounts/{id}/balance` | Stanje računa |
| GET | `/api/accounts/{id}/transactions` | Transakcije računa |
| GET | `/api/accounts/search` | Pretraga računa |
| GET | `/api/transactions` | Lista transakcija (paginacija + filtriranje) |
| POST | `/api/transactions` | Kreiranje transakcije |
| GET | `/api/transactions/{id}` | Detalji transakcije |
| GET | `/api/transactions/search` | Pretraga transakcija |
| POST | `/api/transfer` | Prenos sredstava između računa |
| POST | `/api/users/{id}/change-password` | Promjena lozinke |

### Zaštićene rute — Manager i Admin

| Metoda | Ruta | Opis |
|---|---|---|
| PUT | `/api/accounts/{id}` | Izmjena računa |
| DELETE | `/api/accounts/{id}` | Brisanje računa |
| PUT | `/api/transactions/{id}` | Izmjena transakcije |
| DELETE | `/api/transactions/{id}` | Brisanje transakcije |

### Zaštićene rute — Admin

| Metoda | Ruta | Opis |
|---|---|---|
| GET | `/api/users` | Lista korisnika |
| POST | `/api/users` | Kreiranje korisnika |
| GET | `/api/users/{id}` | Detalji korisnika |
| PUT | `/api/users/{id}` | Izmjena korisnika |
| DELETE | `/api/users/{id}` | Brisanje korisnika |
| GET | `/api/users/search` | Pretraga korisnika |
| PATCH | `/api/users/{id}/role` | Promjena uloge |
| PATCH | `/api/users/{id}/block` | Blokiranje korisnika |
| PATCH | `/api/users/{id}/unblock` | Odblokiravanje korisnika |

---

## Paginacija i filtriranje

Sve `index()` rute podržavaju paginaciju i filtriranje kroz query parametre:

```
GET /api/transactions?per_page=5&page=2
GET /api/transactions?category=Food&date_from=2026-01-01
GET /api/accounts?currency=EUR&status=active
GET /api/users?role=client&is_active=true
```

Response format:
```json
{
    "success": true,
    "data": [...],
    "meta": {
        "current_page": 1,
        "per_page": 10,
        "total": 47,
        "last_page": 5
    }
}
```

---

## Pretraga

```
GET /api/transactions/search?category=Food&search=restoran&account_id=xxx
GET /api/accounts/search?currency=EUR&min_balance=100&max_balance=5000
GET /api/users/search?name=Ana&role=client
```

---

## Transfer sredstava

```
POST /api/transfer
Authorization: Bearer {token}
Body:
{
    "sender_account_id": "uuid",
    "receiver_account_id": "uuid",
    "amount": 100,
    "category": "Transfer",
    "description": "Opis transfera"
}
```

Podržani kursevi:

| Par | Kurs |
|---|---|
| EUR → RSD | 117.20 |
| RSD → EUR | 0.0085 |
| USD → RSD | 108.50 |
| RSD → USD | 0.0092 |
| EUR → USD | 1.08 |
| USD → EUR | 0.93 |

---

## Reset lozinke

```
# Korak 1 — zatraži token
POST /api/forgot-password
Body: { "email": "korisnik@email.com" }

# Korak 2 — resetuj lozinku
POST /api/reset-password
Body:
{
    "email": "korisnik@email.com",
    "token": "token_iz_emaila",
    "password": "novaLozinka123",
    "password_confirmation": "novaLozinka123"
}
```

Token važi 30 minuta i može se iskoristiti samo jednom.

---

## Autori

- Đorđe Jovanović — 2023/0412
- Uroš Jevtić — 2023/0586
- Ivana Jovanović — 2023/0415
