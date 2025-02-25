# Forminator Price Rentier Integration

Wtyczka WordPress integrująca formularz Forminator z API Rentier.io do automatycznej wyceny nieruchomości.

## 📝 Opis

Wtyczka umożliwia automatyczną wycenę nieruchomości na podstawie danych wprowadzonych przez użytkownika w formularzu Forminator. Po wypełnieniu formularza, dane są przesyłane do API Rentier.io, a użytkownik otrzymuje spersonalizowany raport PDF z wyceną na wskazany adres e-mail.

### 🔑 Główne funkcje:
- Integracja z formularzami Forminator
- Automatyczna wycena nieruchomości przez API Rentier.io
- Generowanie spersonalizowanego raportu PDF
- Automatyczne wysyłanie wyceny na e-mail użytkownika
- Możliwość ustawienia ukrytej kopii (BCC) wiadomości e-mail
- Wykorzystanie własnego szablonu PDF (plik JPG z katalogu /templates/)

## 🔧 Instalacja

1. Pobierz wtyczkę
2. Zainstaluj Composer (jeśli nie jest zainstalowany)
3. W katalogu wtyczki uruchom:

```bash
composer install
```

4. Aktywuj wtyczkę w panelu WordPress

## ⚙️ Konfiguracja

1. Przejdź do ustawień wtyczki w panelu WordPress
2. Wprowadź klucz API Rentier.io
3. Wskaż ID formularza Forminator
4. Opcjonalnie: ustaw adres BCC dla kopii wiadomości e-mail

## 📋 Wymagania

- WordPress 5.0 lub nowszy
- Wtyczka Forminator
- PHP 7.4 lub nowszy
- Aktywny klucz API Rentier.io

## 🔍 Dostosowanie

Nazwy pól formularza Forminator można dostosować w kodzie wtyczki. Upewnij się, że odpowiadają one polom w Twoim formularzu.

## 📄 Licencja

GPL v2 lub nowsza

## 🤝 Wsparcie