<?php

namespace App\Http\Controllers;

use App\Enums\Currency;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Exception;

class CurrencyController extends Controller
{
    public function rates(string $base = 'RSD')
    {
        try {
            $base = strtoupper($base);

            if (!in_array($base, array_column(Currency::cases(), 'value'))) {
                return response()->json([
                    'success' => false,
                    'message' => 'Podržane osnovne valute su: RSD, EUR, USD.',
                ], 422);
            }

            $data = Cache::remember("exchange_rates_{$base}", 3600, function () use ($base) {
                $response = Http::timeout(5)->get("https://open.er-api.com/v6/latest/{$base}");

                if (!$response->successful() || $response->json('result') !== 'success') {
                    throw new Exception('Javni servis za kursnu listu nije dostupan.');
                }

                return [
                    'base'         => $base,
                    'last_updated' => $response->json('time_last_update_utc'),
                    'rates'        => collect($response->json('rates'))
                                        ->only(['RSD', 'EUR', 'USD', 'CHF', 'GBP'])
                                        ->toArray(),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Kursna lista je uspešno učitana (keš: 60 min).',
                'data'    => $data,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greška pri učitavanju kursne liste.',
                'error'   => $e->getMessage(),
            ], 503);
        }
    }
    public function countries(string $code)
    {
        try {
            $code = strtoupper($code);

            $data = Cache::remember("currency_countries_{$code}", 86400, function () use ($code) {
                $response = Http::timeout(5)->get(
                    "https://restcountries.com/v3.1/currency/{$code}",
                    ['fields' => 'name,cca2,capital,region']
                );

                if ($response->status() === 404) {
                    return null;
                }

                if (!$response->successful()) {
                    throw new Exception('Javni servis restcountries nije dostupan.');
                }

                return collect($response->json())->map(fn ($c) => [
                    'country' => $c['name']['common'] ?? null,
                    'code'    => $c['cca2'] ?? null,
                    'capital' => $c['capital'][0] ?? null,
                    'region'  => $c['region'] ?? null,
                ])->values()->toArray();
            });

            if ($data === null) {
                return response()->json([
                    'success' => false,
                    'message' => "Valuta {$code} nije pronađena.",
                ], 404);
            }

            return response()->json([
                'success'  => true,
                'currency' => $code,
                'count'    => count($data),
                'data'     => $data,
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Greška pri učitavanju podataka o valuti.',
                'error'   => $e->getMessage(),
            ], 503);
        }
    }
     public static function exchangeRate(string $from, string $to): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        try {
            return Cache::remember("exchange_rate_{$from}_{$to}", 3600, function () use ($from, $to) {
                $response = Http::timeout(5)->get("https://open.er-api.com/v6/latest/{$from}");

                if (!$response->successful() || $response->json('result') !== 'success') {
                    throw new Exception('Servis nije dostupan.');
                }

                $rate = $response->json("rates.{$to}");

                if (!$rate) {
                    throw new Exception('Kurs nije pronađen.');
                }

                return (float) $rate;
            });

        } catch (Exception $e) {
            // Rezervna fiksna kursna lista ako je javni servis nedostupan
            $fallback = [
                'EUR_RSD' => 117.20,
                'RSD_EUR' => 0.0085,
                'USD_RSD' => 108.50,
                'RSD_USD' => 0.0092,
                'EUR_USD' => 1.08,
                'USD_EUR' => 0.93,
            ];

            return $fallback["{$from}_{$to}"] ?? null;
        }
    }
}