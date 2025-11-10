# 🚂 GUIDE COMPLET - Déployer FlexVTC sur RAILWAY (100% GRATUIT)

## 🎯 Résultat Final
Votre site sera accessible sur : **https://flexvtc.up.railway.app**
(ou un nom similaire que vous pourrez personnaliser)

---

## 📋 ÉTAPE 1 : Créer un compte Railway

1. Allez sur : **https://railway.app**
2. Cliquez sur **"Start a New Project"** ou **"Login"**
3. Connectez-vous avec **GitHub** (recommandé) ou **Email**
4. ✅ Vous obtenez **$5 de crédits gratuits/mois** (largement suffisant)

---

## 📋 ÉTAPE 2 : Préparer votre code

### Option A : Via GitHub (RECOMMANDÉ)
1. Créez un repository GitHub (public ou privé)
2. Uploadez tous les fichiers de MonSiteWeb
3. Dans Railway, cliquez sur **"New Project"**
4. Sélectionnez **"Deploy from GitHub repo"**
5. Choisissez votre repository
6. Railway détectera automatiquement le Dockerfile

### Option B : Via CLI Railway
```bash
# Installer Railway CLI
npm i -g @railway/cli

# Se connecter
railway login

# Dans le dossier MonSiteWeb
railway init
railway up
```

---

## 📋 ÉTAPE 3 : Configurer les services

Railway va automatiquement détecter :
- ✅ PHP-Apache (port 8080)
- ✅ MySQL (base de données)
- ✅ MongoDB (base de données)
- ✅ PhpMyAdmin

### Configuration des variables d'environnement
Railway lira automatiquement votre fichier `.env`

Mais vous pouvez aussi les configurer manuellement :
1. Dans Railway Dashboard, cliquez sur votre service
2. Allez dans **"Variables"**
3. Ajoutez :
   - `DB_HOST` : (Railway fournira l'URL MySQL)
   - `DB_NAME` : flexvtc_db
   - `DB_USER` : flex_user
   - `DB_PASS` : flex_userpass
   - `MONGO_HOST` : (Railway fournira l'URL MongoDB)

---

## 📋 ÉTAPE 4 : Personnaliser le nom de domaine

1. Dans Railway Dashboard, cliquez sur votre service web
2. Allez dans **"Settings"** → **"Domains"**
3. Cliquez sur **"Generate Domain"**
4. Vous obtiendrez : `quelquechose.up.railway.app`
5. Vous pouvez personnaliser en : `flexvtc.up.railway.app`

---

## 📋 ÉTAPE 5 : Déploiement

1. Railway déploie automatiquement
2. Attendez 2-5 minutes
3. Cliquez sur l'URL générée
4. ✅ Votre site est en ligne !

---

## 🔍 Vérification

Une fois déployé, testez :
- Page d'accueil : https://flexvtc.up.railway.app/
- Page réservation : https://flexvtc.up.railway.app/reservation.php
- Admin : https://flexvtc.up.railway.app/admin/

---

## 💡 ALTERNATIVES SI RAILWAY NE FONCTIONNE PAS

### Plan B : RENDER.COM
- Même principe que Railway
- Gratuit avec quelques limitations
- Votre site : `flexvtc.onrender.com`
- Guide : https://render.com/docs

### Plan C : INFINITYFREE (Plus simple)
- Pas de Docker, juste PHP + MySQL
- Upload via FTP
- Votre site : `flexvtc.rf.gd`
- Plus facile mais moins de fonctionnalités

---

## ❓ FAQ

**Q : C'est vraiment gratuit ?**
R : Oui ! Railway offre $5/mois de crédits gratuits (suffisant pour un petit site)

**Q : Mon site restera en ligne 24/7 ?**
R : Oui sur Railway. Sur Render gratuit, il s'endort après 15 min d'inactivité.

**Q : Je peux utiliser "flexvtc" exactement ?**
R : Oui, comme sous-domaine : `flexvtc.up.railway.app` ou `flexvtc.onrender.com`

**Q : Et pour un vrai domaine flexvtc.com ?**
R : Il faudra l'acheter (~10€/an) puis le connecter à Railway

---

## 🆘 Besoin d'aide ?

- Documentation Railway : https://docs.railway.app
- Discord Railway : https://discord.gg/railway
- Mon guide : Ce fichier ! 😊

