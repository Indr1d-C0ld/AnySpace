# AnySpace

Piattaforma di social network self-hosted ispirata a MySpace nella sua epoca d'oro (2005-2007): profili personalizzabili a colpi di CSS, un Top 8 di amici, blog, bacheche, gruppi, forum e — naturalmente — Tom, il primo amico che trovi appena ti iscrivi.

![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)

Pensata per essere installata sul proprio server (VPS, homelab, NAS) e condivisa con una cerchia di amici o conoscenti, senza passare da nessuna piattaforma di terze parti.

## Indice

- [Cos'è AnySpace](#cosè-anyspace)
- [Funzionalità](#funzionalità)
- [Tributo a MySpace](#tributo-a-myspace)
- [Stack tecnico](#stack-tecnico)
- [Installazione](#installazione)
- [Sicurezza e hardening](#sicurezza-e-hardening)
- [Struttura del progetto](#struttura-del-progetto)
- [Crediti](#crediti)
- [Licenza](#licenza)

## Cos'è AnySpace

AnySpace è un fork profondamente rivisto di [superswan/anyspace](https://github.com/superswan/anyspace), riportato più vicino possibile all'estetica e alle meccaniche della prima generazione di MySpace, e sottoposto a un giro completo di bonifica su sicurezza, compatibilità e localizzazione.

Non è un clone 1:1 né usa alcun codice, testo o materiale originale di MySpace: è una reinterpretazione libera e open source di quell'epoca, pensata per chi vuole un piccolo social network **proprio**, senza pubblicità, senza algoritmi, senza raccolta dati — solo un posto dove i tuoi contatti possono avere un profilo, scriversi un messaggio, litigare bonariamente nei commenti e magari incollare uno sfondo animato orribile nella propria pagina, come si faceva un tempo.

## Funzionalità

**Profili**
- Sezioni "Chi Sono" / "Chi Vorrei Conoscere" separate, in stile MySpace
- Top 8 amici con ordinamento esplicito, scelto dall'utente
- Tabella Interessi (Generali / Musica / Film / Televisione / Libri / Eroi)
- Umore con selezione a emoji
- Contatore visualizzazioni profilo e "ultimo accesso"
- Foto profilo e brano musicale in sottofondo
- **Il campo "Grafica"**: il classico trucco CSS di MySpace. Incolla un blocco `<style>` e HTML decorativo per riscrivere completamente l'aspetto del tuo profilo — timbri ruotati, animazioni, font personalizzati — tutto sanitizzato server-side (niente XSS, niente breakout dal tag `<style>`) ma con libertà CSS pressoché completa
- Galleria "Grafiche": pubblica il layout del tuo profilo perché altri lo scoprano e lo applichino al proprio
- Tag retro supportati nei testi: `<marquee>`, `<blink>`, `<font>`, oltre a embed da YouTube/Vimeo/Spotify/Bandcamp

**Social**
- Amicizie con richieste, accettazione, rifiuto, blocco
- Preferiti
- Messaggistica privata con badge dei messaggi non letti in navbar
- Commenti a cascata su profili, blog, bulletin, bacheche di gruppo e forum
- Segnalazioni utenti/contenuti, con coda di moderazione lato admin

**Contenuti**
- Blog personale con categorie
- Bulletin (bacheca pubblica) con **scadenza reale** configurabile
- Gruppi tematici con bacheca di discussione propria
- Forum classico a bacheche/discussioni/messaggi, con pannello admin per crearle e riordinarle

**Account e sicurezza**
- Registrazione con verifica e-mail e reset password via e-mail
- Gestione sessioni multiple: vedi da quali dispositivi sei connesso, termina una sessione specifica o tutte le altre
- Pannello amministrazione: utenti (promuovi/banna/reimposta password), segnalazioni, bacheche forum, impostazioni generali del sito

**Mobile**
- Interfaccia pienamente responsive: usabile da smartphone e tablet oltre che da desktop, senza perdere l'estetica originale sopra i 768px di larghezza

## Tributo a MySpace

Questo progetto è un atto d'amore verso un'epoca di internet in cui il tuo profilo era davvero *tuo* — bruttezza inclusa. Il riferimento estetico per il profilo di **Tom** è [wittenbrock/toms-myspace-page](https://github.com/wittenbrock/toms-myspace-page), citato anche nel progetto originale da cui questo fork discende.

**Nota importante**: AnySpace non usa e non include in nessuna forma la fotografia reale di Tom Anderson (co-fondatore di MySpace, persona vivente). L'account "Tom" — il primo amico automatico di ogni nuovo iscritto, fedele al meccanismo originale — usa una mascotte generica, personalizzabile da chi amministra l'istanza con qualunque immagine a piacere. Questo è deliberato: un tributo non ha bisogno di appropriarsi dell'immagine di una persona reale per funzionare.

## Stack tecnico

- **PHP 8+** (nessun framework)
- **MySQL / MariaDB**
- **Apache** o **Nginx** (configurazioni di esempio incluse)
- [HTMLPurifier](https://github.com/ezyang/htmlpurifier) per la sanitizzazione di HTML/CSS generati dagli utenti
- Nessuna build, nessuna dipendenza JavaScript esterna: CSS e markup semplici, come si conviene

## Installazione

1. Clona il repository e servi la cartella `public/` come document root (esempi di configurazione Apache/Nginx inclusi in `example.apache` / `example.nginx`, e `admin.example.apache` / `admin.example.nginx` per proteggere separatamente `/admin`).
2. Crea un database MySQL/MariaDB e importa `schema.sql`.
3. Installa le dipendenze PHP con `composer install` (oppure usa direttamente `vendor/` se già presente nel repository).
4. Copia `core/config.php.example` e `core/email_config.php.example` **fuori dal document root** (es. `/etc/anyspace/config.php`), compilali con i tuoi valori, e aggiorna il percorso in `core/config.php` (costante `ANYSPACE_CONFIG_FILE`) e in `core/email_config.php`. Tenere questi file fuori dal docroot evita che credenziali finiscano mai nell'albero servito da Apache/Nginx.
5. Crea il primo utente amministratore da riga di comando:
   ```
   cd core/tools
   php install.php <nome_admin> <email_admin> <password_admin>
   ```
   Questo crea anche l'utente #1 "Tom", l'amico automatico di ogni futuro iscritto.
6. Assicurati che `public/media/pfp/` e `public/media/music/` siano scrivibili dall'utente del webserver (upload foto profilo e brani).

Configurazione PHP consigliata:
```ini
file_uploads = On
upload_max_filesize = 10M
post_max_size = 15M
max_execution_time = 60
max_input_time = 120
memory_limit = 128M
```

Per la struttura completa del database, vedi [docs/database-schema.md](docs/database-schema.md).

## Sicurezza e hardening

- Tutto l'input utente passa da HTMLPurifier prima di essere salvato; il campo "Grafica" ha un sanitizzatore CSS dedicato che blocca vettori storici (`expression()`, `-moz-binding`, `behavior:`, `javascript:`, `@import`) senza limitare le proprietà CSS disponibili
- Protezione CSRF su tutti i form che modificano stato
- Cookie di sessione con `Secure` / `HttpOnly` / `SameSite=Lax`, rigenerazione dell'id sessione al login
- Il pannello `/admin` va sempre protetto a livello di webserver (vedi `admin.example.apache` / `admin.example.nginx`) oltre che dal controllo applicativo — **non esporlo mai direttamente a internet senza un ulteriore livello di autenticazione**
- Configurazione e segreti vivono fuori dal document root

## Struttura del progetto

```
public/        entry point servito dal webserver (pagine, asset statici, upload)
admin/         pannello di amministrazione (va protetto separatamente)
core/          logica applicativa, sanitizzazione, componenti condivisi
lib/           utility di terze parti minime (password, id univoci)
docs/          documentazione (schema del database)
schema.sql     schema del database, pronto da importare
```

## Crediti

- Fork di [superswan/anyspace](https://github.com/superswan/anyspace) (GPL-3.0), da cui eredita l'impalcatura originale
- Riferimento estetico per il profilo di Tom: [wittenbrock/toms-myspace-page](https://github.com/wittenbrock/toms-myspace-page)
- [HTMLPurifier](https://github.com/ezyang/htmlpurifier) di Edward Z. Yang

## Licenza

GPL-3.0 — vedi [LICENSE](LICENSE). Vuoi cambiare o ridistribuire il codice? Puoi farlo, a patto di mantenere la stessa licenza.
