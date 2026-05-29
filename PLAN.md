# La Récrée Tech — Plateforme e-learning · Plan complet

> **Stack** : Symfony 7.4 LTS + Doctrine ORM + MySQL 8 (MAMP local · o2switch prod) + Twig + Stimulus + Asset Mapper · **Hébergement** `app.larecreetech.com` · **Paiement** Stripe · **Vidéo** Vimeo Pro · **Uploads** locaux `/public/uploads/`

---

## Roadmap par phases

### ✅ Phase 0 — Scaffold (DONE)

- [x] Symfony 7.4.13 LTS installé via `symfony new app --webapp`
- [x] MAMP MySQL 8.0.44 connecté · DB `larecreetech` créée
- [x] `.env.local` configuré (DATABASE_URL MAMP)
- [x] `symfony server:start :8000` opérationnel

---

### 🟡 Phase 1 — Entités (in progress)

11 entités à créer. Chaque entité = `make:entity` ou écriture manuelle + migration + test DB.

#### Phase 1.1 — User ✅ DONE

- [x] User entity : email · password · roles · firstName · lastName · isVerified · verificationToken · resetPasswordToken · resetPasswordExpiresAt · createdAt · updatedAt
- [x] Helpers `isAdmin()` · `isVip()` · `isStudent()` · `getFullName()`
- [x] Migration appliquée · table user 12 colonnes
- [x] Admin seedé : `admin@larecreetech.com` / `admin123` / `ROLE_ADMIN`
- [x] LoginFormAuthenticator + LoginController + login.html.twig
- [x] DashboardController + dashboard.html.twig

#### Phase 1.2 — Formation 🔜

- [ ] Formation entity
  - id · slug (unique) · title · subtitle · description (text) · price (int cents) · currency (default EUR) · coverImage (string path nullable) · vimeoFolderId (string nullable) · published (bool) · displayOrder (int) · createdAt · updatedAt
- [ ] Repository : `findPublished()` · `findBySlug()`
- [ ] Helper `getPriceFormatted()` (cents → "397 €")
- [ ] Migration + test

#### Phase 1.3 — Module 🔜

- [ ] Module entity (chapitre d'une formation)
  - id · formation_id (FK) · title · slug · description (text nullable) · displayOrder · createdAt
- [ ] Cascade delete + orphan removal
- [ ] Migration + test

#### Phase 1.4 — Lesson 🔜

- [ ] Lesson entity
  - id · module_id (FK) · title · slug · vimeoVideoId (string) · description (text) · durationSeconds (int) · displayOrder · createdAt · updatedAt
- [ ] Helper `getDurationFormatted()` ("12 min 34 s")
- [ ] Migration + test

#### Phase 1.5 — Resource 🔜

- [ ] Resource entity (lien ou fichier attaché à une leçon)
  - id · lesson_id (FK) · type (enum: link · file) · title · url (string nullable, pour type link) · filePath (string nullable, pour type file) · createdAt
- [ ] Validators : url OR filePath obligatoire selon type
- [ ] Migration + test

#### Phase 1.6 — Enrollment 🔜

- [ ] Enrollment entity (inscription user à une formation)
  - id · user_id · formation_id · source (enum: stripe · vip · admin) · stripeSessionId (nullable) · stripePaymentIntentId (nullable) · amountPaid (cents nullable) · createdAt
- [ ] Unique constraint (user_id + formation_id)
- [ ] Helper `isVipGranted()` · `isPaid()`
- [ ] Migration + test

#### Phase 1.7 — LessonProgress 🔜

- [ ] LessonProgress entity (progression user dans une leçon)
  - id · enrollment_id (FK) · lesson_id (FK) · watchedSeconds (int) · percentWatched (int 0-100) · completedAt (datetime nullable) · lastWatchedAt (datetime) · createdAt
- [ ] Unique constraint (enrollment_id + lesson_id)
- [ ] Migration + test

#### Phase 1.8 — Comment 🔜

- [ ] Comment entity (commentaires leçon, threadés)
  - id · lesson_id (FK) · user_id (FK) · parent_id (self-FK nullable, threading) · content (text) · status (enum: pending · approved · rejected) · createdAt · updatedAt
- [ ] Relation `OneToMany` `$replies`
- [ ] Helper `isApproved()` · `hasReplies()`
- [ ] Migration + test

#### Phase 1.9 — Certificate 🔜

- [ ] Certificate entity (certificat de fin de formation)
  - id · user_id · formation_id · issuedAt · pdfPath · code (uuid string unique)
- [ ] Migration + test

#### Phase 1.10 — Payment 🔜

- [ ] Payment entity (logs Stripe — optionnel mais utile pour audit)
  - id · user_id · formation_id · stripeSessionId · stripePaymentIntentId · amountCents · currency · status (enum: pending · succeeded · failed · refunded) · createdAt · updatedAt
- [ ] Migration + test

---

### 🟡 Phase 2 — Auth & DA frontale

- [ ] Restyler login en **DA La Récrée Tech** (cream/tableau · récrée mark · Fraunces · rose framboise)
- [ ] Créer `base.html.twig` layout marque (header logo · footer)
- [ ] Page register (post-paiement) avec verification email
- [ ] Mot de passe oublié (request + reset form + email)
- [ ] Vérification email après inscription (token + lien email)
- [ ] Dashboard étudiant : liste formations achetées + dernière leçon vue + % progression
- [ ] Profil utilisateur : édition firstName · lastName · password
- [ ] Bundle `symfonycasts/verify-email-bundle` pour vérification email
- [ ] Bundle `symfonycasts/reset-password-bundle` pour reset

---

### 🟡 Phase 3 — Lecture cours

- [ ] Vue formation : modules + leçons + progression globale
- [ ] Vue leçon : Vimeo embed + description + ressources
- [ ] Player tracking : enregistre `watchedSeconds` toutes les 10s via Stimulus + fetch API
- [ ] Auto-validation à 95 % vidéo → `completedAt = now()`
- [ ] Auto-redirect leçon suivante 3 s après fin
- [ ] Navigation prev/next leçon
- [ ] Sidebar arborescence module/leçon (icons ✓ pour validées)
- [ ] Ressources : liens externes (target blank) + fichiers (download)

---

### 🟡 Phase 4 — Stripe checkout

- [ ] Install `stripe/stripe-php`
- [ ] `.env` : `STRIPE_PUBLIC_KEY` + `STRIPE_SECRET_KEY` + `STRIPE_WEBHOOK_SECRET`
- [ ] StripeService (création checkout session + retrieve)
- [ ] Route `/formations/{slug}/checkout` → crée Checkout Session Stripe
- [ ] Redirect Stripe Checkout
- [ ] Page `/checkout/success?session_id=` après paiement
- [ ] Webhook `/stripe/webhook` :
  - `checkout.session.completed` → crée User (si nouveau) + Enrollment + Payment
  - Envoie email de bienvenue avec lien set password
  - `charge.refunded` → marque Enrollment annulé
- [ ] Test webhook en local : Stripe CLI `stripe listen`

---

### 🟡 Phase 5 — Admin (EasyAdmin)

- [ ] Install `easycorp/easyadmin-bundle`
- [ ] Dashboard `/admin` : stats globales (nb users · nb enrollments · CA total)
- [ ] CRUD Formation (création formation complète avec upload cover)
- [ ] CRUD Module (lié à formation)
- [ ] CRUD Lesson (lié à module · champ Vimeo ID)
- [ ] CRUD Resource (lien ou fichier upload)
- [ ] User management : édition rôles (toggle ROLE_VIP · ROLE_ADMIN)
- [ ] Assigner VIP → bouton "Offrir Formation Claude" sur user
- [ ] Moderation Comment : approuver/rejeter en lot
- [ ] Sécurisé par `ROLE_ADMIN`

---

### 🟡 Phase 6 — Commentaires

- [ ] Form commentaire sous chaque leçon (Stimulus controller)
- [ ] Affichage : threadé 2 niveaux max (commentaire + réponses)
- [ ] Statut `pending` à la création → admin valide
- [ ] Email à admin sur nouveau commentaire à modérer
- [ ] Email aux participants thread sur nouvelle réponse approuvée
- [ ] Édition/suppression de son propre commentaire (24h max)

---

### 🟡 Phase 7 — Certificat PDF

- [ ] Install `dompdf/dompdf`
- [ ] Template `certificate.html.twig` (design DA · grid pattern · récrée mark · signature Rama · code uuid)
- [ ] CertificateService : génère PDF à la complétion 100 % d'une formation
- [ ] Stockage `/public/uploads/certificates/{uuid}.pdf`
- [ ] Email de félicitations avec PDF en pièce jointe
- [ ] Page publique `/certificat/{code}` pour vérification (affiche détails sans login)
- [ ] Bouton "Télécharger certificat" sur dashboard

---

### 🟡 Phase 8 — Emails transactionnels & légal

- [ ] Templates email base (header logo · footer mentions) Twig
- [ ] Emails à créer :
  - Bienvenue (post-paiement) avec lien set password
  - Vérification email
  - Reset password
  - Nouveau commentaire à modérer (admin)
  - Réponse à ton commentaire
  - Félicitations fin de formation + certificat
  - Notification VIP assignée
- [ ] Configuration MAILER_DSN : Resend/Postmark/Brevo en prod
- [ ] Pages légales :
  - `/mentions-legales`
  - `/cgv` (avec mention 14 jours rétractation + droit d'auteur cours)
  - `/confidentialite` (RGPD · Stripe · MAMP/o2switch · Vimeo)
- [ ] Cookie banner si analytics ajoutées

---

### 🟡 Phase 9 — Déploiement o2switch

- [ ] DNS : sous-domaine `app.larecreetech.com` → IP o2switch
- [ ] cPanel : créer le sous-domaine + redirect HTTPS Let's Encrypt
- [ ] Créer DB MySQL prod via cPanel
- [ ] Upload via SFTP/Git : `app/` dossier complet
- [ ] `.env.prod.local` : DATABASE_URL prod + APP_ENV=prod
- [ ] `composer install --no-dev --optimize-autoloader`
- [ ] `php bin/console doctrine:migrations:migrate --no-interaction` en prod
- [ ] `php bin/console cache:clear` + `cache:warmup` env prod
- [ ] Vérifier permissions `var/` et `public/uploads/` (775)
- [ ] Test Stripe webhook prod
- [ ] Test envoi mail prod
- [ ] Configurer `app/public/.htaccess` (Symfony route fallback)
- [ ] Backup MySQL automatique cPanel (1×/jour)

---

## Architecture entités (résumé relationnel)

```
User ──< Enrollment >── Formation ──< Module ──< Lesson ──< Resource
 │                       │            │           │           
 │                       │            │           │
 │                       │            │           ├──< Comment >─ User (auteur)
 │                       │            │           │
 │                       │            │           └──< LessonProgress
 │                       │            │                    │
 │                       │            │                    └── Enrollment
 │                       │            │
 │                       └──< Certificate
 │
 └──< Payment
```

---

## Stack & conventions

### PHP / Symfony
- **Strict types** sur tous les fichiers
- **Final** sur Controllers et Services
- **PHP 8 attributes** pour Doctrine (pas d'annotations)
- **PSR-12** code style (vendor/bin/phpcs)
- **Readonly properties** quand applicable

### Doctrine
- Soft delete pas utilisé (sauf si besoin futur)
- Timestamps : `createdAt` + `updatedAt` sur entités mutables
- Slugs : générés via `cocur/slugify` ou helper maison

### Sécurité
- Roles : `ROLE_USER` (base) · `ROLE_STUDENT` · `ROLE_VIP` · `ROLE_ADMIN`
- ROLE_ADMIN hérite ROLE_VIP + ROLE_STUDENT + ROLE_USER (security.yaml `role_hierarchy`)
- Sessions stockées en base ou Redis
- CSRF activé sur tous les forms
- Password hashing : bcrypt cost 13 (défaut)

### Front
- Twig pour SSR (pas de SPA)
- Stimulus pour interactivité (player vidéo · commentaires · upload)
- Asset Mapper (pas Webpack Encore)
- CSS vanilla avec variables (palette Récrée Tech)

---

## Variables CSS marque

```css
:root {
  --rose: #C8395E;
  --rose-light: #E8587A;
  --rose-deep: #A82248;
  --rose-pale: #F0D6D6;
  --ink: #14110D;
  --ink-soft: #4A4540;
  --cream: #FCFAF5;
  --cream-warm: #F5EEDB;
  --tableau: #1F3025;
  --tableau-light: #2D4438;
  --line: rgba(20, 17, 13, 0.10);
  --grid: rgba(20, 17, 13, 0.05);
  --grid-dark: rgba(252, 250, 245, 0.08);
}
```

Fontes : **Fraunces** (titres italiques) · **Manrope** (body) · **DM Mono** (eyebrows caps) · **Caveat** (signatures manuscrites).

---

## Commands utiles

```bash
# Démarrer projet
cd app && symfony server:start --no-tls --port=8000 -d

# Stop serveur
symfony server:stop

# Logs
symfony server:log

# Cache
php bin/console cache:clear

# Migrations
php bin/console make:migration
php bin/console doctrine:migrations:migrate --no-interaction

# Console DB direct
/Applications/MAMP/Library/bin/mysql80/bin/mysql -u root -proot -h 127.0.0.1 -P 8889 larecreetech

# Maker
php bin/console make:entity      # création entité
php bin/console make:controller  # controller
php bin/console make:form         # form type
php bin/console make:command      # console command

# Hash password
php bin/console security:hash-password 'monpass'
```
