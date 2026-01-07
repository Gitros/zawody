Dokumentacja projektu - System zawodów sportowych

1. Temat projektu

Zawody sportowe - wyniki zawodników w konkurencjach

2. Autor
   Jakub Owczarek

3. Cel aplikacji

Aplikacja służy do obsługi prostych zawodów sportowych. Użytkownik (organizator) po zalogowaniu może zarządzać zawodnikami, konkurencjami oraz wynikami. Aplikacja umożliwia filtrowanie wyników, tworzenie klasyfikacji oraz eksport danych do pliku tekstowego.

4. Wymagania funkcjonalne

Logowanie organizatora (autoryzacja na podstawie loginu i hasła).

Zarządzanie zawodnikami (CRUD + wyszukiwanie).

Zarządzanie konkurencjami (CRUD + wyszukiwanie).

Dodawanie wyników (zawodnik + konkurencja + wartość + data).

Edycja i usuwanie wyników.

Filtrowanie wyników po zawodniku i/lub konkurencji.

Automatyczne sortowanie i tabela klasyfikacji dla wybranej konkurencji (najlepsze wyniki).

Eksport wyników do pliku tekstowego .txt.

5. Technologie i narzędzia

PHP – logika aplikacji, obsługa formularzy, sesje, komunikacja z bazą

MySQL (XAMPP) – baza danych

HTML + CSS – interfejs użytkownika

PDO – połączenie z bazą danych

6. Struktura projektu (najważniejsze pliki i katalogi)
   6.1. Katalog app/

config.php
Plik konfiguracyjny z danymi do bazy danych: host, nazwa bazy, użytkownik, hasło.

db.php
Funkcja do połączenia z bazą danych:

getDb(): PDO – tworzy połączenie PDO z MySQL oraz ustawia tryb błędów na wyjątki.

auth.php
Funkcje odpowiedzialne za sesję i dostęp:

startSession() – uruchamia sesję, jeśli nie jest uruchomiona,

isLoggedIn() – sprawdza czy użytkownik jest zalogowany,

requireLogin() – jeśli użytkownik nie jest zalogowany, przekierowuje do login.php.

header.php / footer.php
Wspólny nagłówek i stopka strony. Zawierają nawigację, sekcję hero (dla zalogowanego użytkownika) oraz kontener .content.

6.2. Katalog public/

index.php
Przekierowanie: jeśli zalogowany → dashboard.php, jeśli nie → login.php.

login.php
Strona logowania organizatora.
Sprawdza dane w tabeli users i używa password_verify() do weryfikacji hasła.
Po poprawnym zalogowaniu zapisuje do sesji user_id i username.

logout.php
Wylogowanie – niszczy sesję (session_destroy()) i przekierowuje do login.php.

dashboard.php
Panel organizatora z kafelkami do modułów + liczniki rekordów + tabela „ostatnie wyniki”.

athletes.php
Moduł „Zawodnicy”:

dodawanie, edycja, usuwanie zawodników

wyszukiwanie po imieniu/nazwisku (LIKE)

events.php
Moduł „Konkurencje”:

dodawanie, edycja, usuwanie konkurencji

wyszukiwanie po nazwie

results.php
Moduł „Wyniki”:

dodawanie wyniku (zawodnik + konkurencja + wartość + data)

edycja/usuwanie wyniku

filtrowanie po zawodniku i po konkurencji

walidacja wartości (musi być liczbą)

ranking.php
Moduł „Klasyfikacja”:

wybór konkurencji

wybór trybu: „mniejszy wynik lepszy” lub „większy wynik lepszy”

tabela miejsc (ranking) na podstawie najlepszego wyniku zawodnika:

MIN(wartosc) dla trybu asc,

MAX(wartosc) dla trybu desc.

export.php
Moduł „Eksport”:

generuje raport .txt na podstawie wyników z bazy

zapis pliku do folderu storage/exports/

umożliwia pobranie pliku w przeglądarce

6.3. Katalog storage/exports/

Folder, w którym zapisywane są wygenerowane raporty .txt.
Aplikacja tworzy folder automatycznie, jeśli nie istnieje (w razie potrzeby).

6.4. Katalog sql/

schema.sql – tworzy bazę i tabele

seed.sql – wstawia dane testowe oraz konto admin

7. Baza danych (struktura i tabele)
   7.1. Tabele

users

id – klucz główny

username – login (unikalny)

password_hash – zahashowane hasło (bcrypt)

zawodnik

id – klucz główny

imie

nazwisko

konkurencja

id – klucz główny

nazwa

wynik

id – klucz główny

zawodnik_id – FK do zawodnik(id)

konkurencja_id – FK do konkurencja(id)

wartosc – wynik (DECIMAL)

data_wyniku – data wyniku (DATE)
Relacje mają ON DELETE CASCADE, więc usunięcie zawodnika lub konkurencji usuwa powiązane wyniki.

8. Przechowywanie danych (format i miejsce)

Dane aplikacji są przechowywane głównie w bazie MySQL.

Konfiguracja bazy znajduje się w pliku app/config.php.

Eksport wyników zapisuje raporty do plików tekstowych w storage/exports/.

9. Schemat powiązań widoków (nawigacja / przepływ stron)

index.php

jeśli zalogowany → dashboard.php

jeśli niezalogowany → login.php

login.php

logowanie poprawne → dashboard.php

błędne dane → komunikat na stronie

dashboard.php (Panel)

linki do:

athletes.php (Zawodnicy)

events.php (Konkurencje)

results.php (Wyniki)

ranking.php (Klasyfikacja)

export.php (Eksport)

logout.php (Wyloguj)

Strony modułów (athletes.php, events.php, results.php, ranking.php, export.php)

dostępne tylko po zalogowaniu (sprawdzane przez requireLogin())

10. Instrukcja uruchomienia projektu (XAMPP)

Skopiować folder projektu do:

xampp/htdocs/zawody/ (lub innej nazwy)

Uruchomić w XAMPP:

Apache

MySQL

Wejść do phpMyAdmin i zaimportować pliki SQL:

najpierw sql/schema.sql

potem sql/seed.sql

Wejść w przeglądarce:

http://localhost/zawody/public/

Dane logowania testowego

login: admin

hasło: admin123

11. Uwagi końcowe

Aplikacja wykorzystuje sesje do autoryzacji użytkownika.

Hasła w bazie nie są przechowywane jawnie – używany jest password_hash i password_verify.

Walidacja danych jest realizowana po stronie PHP (weryfikacja pustych pól, długości tekstu, liczby w wyniku).
