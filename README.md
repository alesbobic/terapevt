# terapevt.si — modernizirana verzija

## Kaj je vključeno
- prenovljen `index.html`
- sodoben responsive UI v `assets/css/modern.css`
- mobilni meni, animacije in AJAX obrazec v `assets/js/modern.js`
- posodobljen `contact.php`
- ohranjen PHPMailer v `PHPMailer-master/`

## Nastavitev pošiljanja pošte
Obrazec uporablja PHPMailer. SMTP podatkov ni več v kodi. Nastavite jih v okolju strežnika:

- `CONTACT_TO` — prejemnik, privzeto `tomaz@terapevt.si`
- `CONTACT_FROM` — pošiljatelj, privzeto `tomaz@terapevt.si`
- `SMTP_HOST`
- `SMTP_USER`
- `SMTP_PASS`
- `SMTP_PORT`, privzeto `587`
- `SMTP_SECURE`, privzeto `tls`

Če SMTP spremenljivke niso nastavljene, PHPMailer poskusi poslati prek privzetega PHP mail transporta.
