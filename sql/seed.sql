USE zawody;

/* =========================
   UŻYTKOWNIK TESTOWY (ORGANIZATOR)
   login: admin
   hasło: admin123
========================= */

DELETE FROM users WHERE username = 'admin';

INSERT INTO users (username, password_hash)
VALUES ('admin', '$2y$10$e0NRi4h.qmHjT1C7m0qPZezI2lY8VQqGQJqvU5yOQy2w3p8m7s9cS');

/* =========================
   ZAWODNICY
========================= */
INSERT INTO zawodnik (imie, nazwisko) VALUES
('Jan', 'Kowalski'),
('Adam', 'Nowak'),
('Piotr', 'Zieliński'),
('Michał', 'Wiśniewski'),
('Tomasz', 'Lewandowski');

/* =========================
   KONKURENCJE
========================= */
INSERT INTO konkurencja (nazwa) VALUES
('Bieg na 100 m'),
('Bieg na 400 m'),
('Skok w dal'),
('Rzut oszczepem');

/* =========================
   WYNIKI
========================= */
INSERT INTO wynik (zawodnik_id, konkurencja_id, wartosc, data_wyniku) VALUES
-- Bieg 100 m (czas – mniejszy lepszy)
(1, 1, 11.23, '2025-01-10'),
(2, 1, 10.98, '2025-01-10'),
(3, 1, 11.45, '2025-01-10'),

-- Bieg 400 m
(1, 2, 52.10, '2025-01-11'),
(4, 2, 50.75, '2025-01-11'),

-- Skok w dal (większy lepszy)
(2, 3, 7.35, '2025-01-12'),
(5, 3, 6.95, '2025-01-12'),

-- Rzut oszczepem
(3, 4, 61.20, '2025-01-13'),
(4, 4, 64.80, '2025-01-13');
