\documentclass[a4paper,12pt]{article}

% ==================== PAQUETS ====================
\usepackage[french]{babel}
\usepackage[utf8]{inputenc}
\usepackage[T1]{fontenc}
\usepackage{geometry}
\geometry{margin=2cm}
\usepackage{graphicx}
\usepackage{hyperref}
\hypersetup{
    colorlinks=true,
    linkcolor=blue,
    urlcolor=blue
}
\usepackage{tabularx}
\usepackage{array}
\usepackage{enumitem}
\usepackage{fancyhdr}
\usepackage{tcolorbox}
\tcbuselibrary{skins,breakable}
\usepackage{xcolor}
\usepackage{amssymb}

% ==================== VARIABLES GLOBALES ====================
\newcommand{\contactmail}{administration@maisondeschaudoudoux.fr}
\newcommand{\contacttel}{01 23 45 67 89} % À remplacer par le vrai numéro

% ==================== BOÎTES RÉUTILISABLES ====================

% Boîte "Étapes" numérotées, très visuelle
\newtcolorbox{etapes}[1]{
    colback=blue!4!white, colframe=blue!55!black,
    boxrule=1.2pt, arc=2mm,
    title={\textbf{#1}},
    fonttitle=\large\bfseries,
    breakable
}

% Boîte "Important" — rouge, grande, pour les pièges qui font perdre de l'argent/du temps
\newtcolorbox{important}[1][Important]{
    colback=red!6!white, colframe=red!60!black,
    boxrule=1.5pt, arc=2mm,
    title={\Large \textbf{\textcolor{white}{$\star$~#1~$\star$}}},
    coltitle=white, colbacktitle=red!60!black,
    fonttitle=\bfseries,
    breakable
}

% Boîte "Si..." — jaune, pour les cas particuliers / dépannage
\newtcolorbox{siboite}[1]{
    colback=yellow!8!white, colframe=orange!70!black,
    boxrule=1pt, arc=2mm,
    title={\textbf{Si #1}},
    fonttitle=\bfseries,
    breakable
}

% Boîte "Bon à savoir" — verte, information rassurante
\newtcolorbox{bonasavoir}{
    colback=green!5!white, colframe=green!45!black,
    boxrule=1pt, arc=2mm,
    title={\textbf{Bon à savoir}},
    fonttitle=\bfseries,
    breakable
}

% Repère photo — remplace une vraie capture d'écran par un cadre gris à compléter
\newcommand{\photo}[2]{%
\begin{center}
\fbox{\includegraphics[width=#1\textwidth]{#2}}
\end{center}
}

% ==================== EN-TÊTE / PIED DE PAGE ====================
\setlength{\headheight}{14pt}
\pagestyle{fancy}
\fancyhf{}
\fancyhead[L]{Guide Espace Intervenant·e}
\fancyhead[R]{\thepage}
\fancyfoot[C]{\small Besoin d'aide ? \contacttel\ -- \contactmail}

% ==================== DOCUMENT ====================
\begin{document}

% ============================================================
% PAGE DE TITRE
% ============================================================
\begin{titlepage}
    \centering
    \vspace*{2cm}
    {\Huge \textbf{Guide d'utilisation}} \\[0.5cm]
    {\Large \textbf{Espace Intervenant·e}} \\[0.3cm]
    {\large \textit{Pas à pas, avec des images à chaque étape}} \\[1.5cm]
    \includegraphics[width=0.4\textwidth]{images/logo-exemple.png} \\[1cm] % À remplacer par le vrai logo
    \vfill
    \begin{tcolorbox}[colback=blue!4!white, colframe=blue!55!black, width=0.8\textwidth, arc=2mm]
        \centering
        \textbf{Une question en cours de route ?}\\
        Appelez-nous au \textbf{\contacttel}\\
        ou écrivez à \textbf{\contactmail}\\
        \textit{Personne ne vous en voudra de nous appeler.}
    \end{tcolorbox}
    \vfill
    {\large Version 3.0 -- \today}
\end{titlepage}

% ============================================================
% LES 4 CHOSES À RETENIR — résumé ultra-court, en tout premier
% ============================================================
\thispagestyle{empty}
\section*{Les 4 choses à retenir}

\begin{center}
\textit{Si vous ne devez retenir que 4 choses de tout ce guide, ce sont celles-ci.\\
Le reste du guide n'est là que pour vous aider si vous êtes bloqué·e.}
\end{center}

\vspace{0.5cm}

\begin{etapes}{Le rythme de tous les jours}
\begin{enumerate}[label=\Large\textbf{\arabic*.}, leftmargin=1.2cm, itemsep=12pt]
    \item \large \textbf{Vous vous connectez} avec votre numéro de téléphone et votre mot de passe.
    \item \large \textbf{En arrivant chez une famille}, vous appuyez sur \textbf{Démarrer}.
    \item \large \textbf{En partant de chez une famille}, vous appuyez sur \textbf{Terminer}.\\
    \textcolor{red!70!black}{\textbf{Sans ce bouton, vos heures ne sont pas enregistrées.}}
    \item \large \textbf{À la fin du mois}, vous vérifiez vos heures et vous les \textbf{signez}.
\end{enumerate}
\end{etapes}

\vspace{0.3cm}
\begin{center}
\textit{C'est tout. Avec ces 4 étapes, vous pouvez déjà utiliser l'application.}\\
\textit{La suite du guide explique chaque étape en détail, avec des images.}
\end{center}

\newpage

% ============================================================
% TABLE DES MATIÈRES
% ============================================================
\tableofcontents
\newpage

% ============================================================
\section{Comment lire ce guide}
\label{sec:conventions}
% ============================================================

\begin{itemize}[itemsep=6pt]
    \item Chaque action que vous devez faire est écrite en \textbf{gras}, par exemple : appuyez sur \textbf{Démarrer}.
    \item Une capture d'écran (une photo de ce que vous voyez à l'écran) accompagne presque chaque étape. Repérez-vous d'abord avec l'image, lisez le texte seulement si besoin.
    \item Les encadrés \textcolor{orange!70!black}{\textbf{orange « Si... »}} répondent aux questions les plus courantes.
    \item Les encadrés \textcolor{red!60!black}{\textbf{rouges}} signalent une information très importante à ne pas oublier.
    \item Vous ne trouvez pas la réponse à votre question ? Appelez-nous au \textbf{\contacttel}. C'est plus rapide que de chercher seul·e.
\end{itemize}

% ============================================================
\section{Se repérer sur l'écran}
\label{sec:menu}
% ============================================================

Sur toutes les pages de l'application, vous retrouvez la même barre en haut de l'écran. C'est votre point de repère.

\photo{1}{images/intervenant-00-menu.png}

\begin{siboite}{vous êtes perdu·e}
\textbf{\textbf{$\rightarrow$} Appuyez toujours sur Accueil ou sur chadoudoux} en haut de l'écran. Vous revenez à la page de départ, et vous pouvez recommencer.
\end{siboite}

% ============================================================
\section{Se connecter}
\label{sec:connexion}
% ============================================================

\subsection{La toute première fois}

Cette étape ne se fait \textbf{qu'une seule fois}, quand vous utilisez l'application pour la première fois.

\begin{etapes}{Pour créer votre compte Intervenant}
\begin{enumerate}[itemsep=8pt]
    \item Ouvrez l'application.
    \item Appuyez sur \textbf{Intervenant}.
\end{enumerate}
\end{etapes}

\photo{0.6}{images/intervenant-01a-choix-profil.png}

\begin{etapes}{}
\begin{enumerate}[itemsep=8pt, start=3]
    \item Appuyez sur \textbf{Créer un compte}.
    \item Écrivez votre \textbf{numéro de téléphone} (celui utilisé avec l'entreprise).
    \item Choisissez un \textbf{mot de passe} (au moins 8 lettres ou chiffres).
    \item Écrivez ce même mot de passe une deuxième fois, pour confirmer.
    \item Appuyez sur \textbf{Valider l'inscription}.
\end{enumerate}
\end{etapes}

\photo{0.6}{images/intervenant-01b-inscription.png}

\begin{bonasavoir}
Votre compte est prêt. La prochaine fois, vous n'aurez plus besoin de vous inscrire : vous vous connecterez directement (voir ci-dessous).
\end{bonasavoir}

\subsection{Tous les jours}

\begin{etapes}{Pour vous connecter}
\begin{enumerate}[itemsep=8pt]
    \item Ouvrez l'application.
    \item Écrivez votre \textbf{numéro de téléphone}.
    \item Écrivez votre \textbf{mot de passe}.
    \item Appuyez sur \textbf{Connexion}.
\end{enumerate}
\end{etapes}

\photo{0.6}{images/intervenant-01c-connexion.png}

\begin{siboite}{vous avez oublié votre mot de passe}
\textbf{\textbf{$\rightarrow$} Appuyez sur « Mot de passe oublié ? »}. Un message est envoyé sur votre e-mail avec un nouveau code. Ce code est valable 1 heure.\\[4pt]
Vous ne trouvez pas le message ? Regardez dans le dossier \textbf{« Indésirables »} ou \textbf{« Spam »} de votre boîte mail.
\end{siboite}

% ============================================================
\section{La page d'accueil}
\label{sec:accueil}
% ============================================================

C'est la page que vous voyez juste après vous être connecté·e. C'est votre tableau de bord : elle résume votre mois.

\photo{1}{images/intervenant-02-accueil.png}

Vous y trouvez :
\begin{itemize}[itemsep=6pt]
    \item En haut : votre nom, avec un accès à \textbf{Mon profil}.
    \item Au milieu : quelques chiffres qui résument votre mois (total d'heures, heures déjà validées, etc.).
    \item Des raccourcis vers les actions les plus utiles : \textbf{Pointer} (qui regroupe la saisie manuelle, le scan QR et la garde d'enfant), \textbf{Scanner QR}, votre planning.
\end{itemize}

\begin{siboite}{un message d'alerte s'affiche en haut de l'écran}
C'est probablement pour vous rappeler de signer votre relevé du mois précédent. \textbf{\textbf{$\rightarrow$} Appuyez sur le bouton du message} pour être amené·e directement à la bonne page.
\end{siboite}

% ============================================================
\section{Pointer votre arrivée et votre départ}
\label{sec:qrcode}
\index{QR}
% ============================================================

C'est la façon la plus simple et la plus fiable de déclarer vos heures. Chaque famille a une petite affiche avec un \textbf{QR Code} (une image carrée avec un motif, qui se lit avec l'appareil photo de votre téléphone).

\begin{important}[Un seul geste à ne jamais oublier]
Vous devez appuyer sur \textbf{Terminer} quand vous partez. \\[6pt]
\Large \textbf{Si vous oubliez, vos heures ne seront pas enregistrées, et vous risquez de ne pas être payé·e pour cette intervention.}\\[6pt]
\normalsize En cas de doute, contactez-nous au \textbf{\contacttel} : nous pourrons corriger l'oubli.
\end{important}

\subsection{Pour commencer une intervention (à votre arrivée)}

\begin{etapes}{À faire dès que vous arrivez chez la famille}
\begin{enumerate}[itemsep=10pt]
    \item Ouvrez l'appareil photo de votre téléphone, ou appuyez sur \textbf{Scanner QR} dans l'application.
    \item Visez le QR Code affiché chez la famille.
\end{enumerate}
\end{etapes}

\photo{0.55}{images/intervenant-03a-scan.png}

\begin{etapes}{}
\begin{enumerate}[itemsep=10pt, start=3]
    \item Vérifiez que le \textbf{nom de la famille} qui s'affiche est le bon.
     \item Sélectionnez le type de prestation \textbf{Ménage ou Garde} : par défaut, c'est votre prestation habituelle (vous pouvez changer si besoin).
    \item Appuyez sur \textbf{Démarrer}.
\end{enumerate}
\end{etapes}

% \photo{0.55}{images/intervenant-03b-demarrer.png}

\begin{bonasavoir}
C'est fait ! Un compteur se met en route. Vous n'avez plus rien à faire jusqu'à votre départ.
\end{bonasavoir}

\subsection{Pour terminer une intervention (à votre départ)}

\begin{etapes}{À faire juste avant de partir de chez la famille}
\begin{enumerate}[itemsep=10pt]
    \item Ouvrez de nouveau l'appareil photo, ou appuyez sur \textbf{Scanner QR}.
    \item Visez le même QR Code que tout à l'heure.
\end{enumerate}
\end{etapes}

\photo{0.55}{images/intervenant-04a-rescan.png}

\begin{etapes}{}
\begin{enumerate}[itemsep=10pt, start=3]
    \item Vérifiez l'heure de début affichée : c'est bien l'heure de votre arrivée.
    \item Appuyez sur \textbf{Terminer}.
\end{enumerate}
\end{etapes}

\photo{0.55}{images/intervenant-04b-terminer.png}

\begin{bonasavoir}
Une heure de début ou de fin à corriger ? Appuyez sur \textbf{Modifier les heures} : vous ajustez le début et la fin affichés, sans interrompre le comptage.
\end{bonasavoir}

\begin{siboite}{le QR Code ne se scanne pas}
\begin{itemize}[itemsep=4pt]
    \item Éloignez ou rapprochez légèrement votre téléphone.
    \item Allumez plus de lumière si la pièce est sombre.
    \item Toujours bloqué ? \textbf{\textbf{$\rightarrow$} Utilisez la saisie manuelle} (section~\ref{sec:saisie-manuelle}).
\end{itemize}
\end{siboite}

\begin{siboite}{vous intervenez plusieurs fois chez la même famille le même jour}
C'est possible (par exemple un ménage le matin et un autre l'après-midi) : faites simplement \textbf{Démarrer} puis \textbf{Terminer} à chaque passage. La seule règle : \textbf{terminez un pointage avant d'en démarrer un nouveau} (l'application vous prévient si un pointage est encore en cours).
\end{siboite}

% ============================================================
\section{Déclarer une garde d'enfant}
\label{sec:pointage-tel}
% ============================================================

Pour la \textbf{garde d'enfant}, une méthode spéciale vous montre directement \textbf{votre planning de garde du jour} : vous n'avez qu'à choisir la famille, sans scanner de QR Code à l'arrivée.

\photo{0.6}{images/intervenant-05-pointage-tel.png}

\begin{etapes}{À votre arrivée -- démarrer la garde}
\begin{enumerate}[itemsep=10pt]
    \item Ouvrez la page \textbf{Garde d'enfant} (depuis \textbf{Pointer}, ou en scannant le QR « Garde »).
    \item Écrivez votre \textbf{numéro de téléphone}, puis appuyez sur \textbf{Voir mes gardes du jour}.
    \item La liste de vos \textbf{gardes prévues aujourd'hui} s'affiche (famille + horaires).
    \item \textbf{Appuyez sur la famille} chez qui vous intervenez.
    \item Vérifiez les informations, puis appuyez sur \textbf{Démarrer}.
\end{enumerate}
\end{etapes}

\begin{important}[Pour terminer une garde]
Pour \textbf{Terminer} (ou modifier) une garde, vous devez \textbf{scanner le QR Code de la famille} sur place. \\[4pt]
\normalsize C'est ce qui prouve que vous étiez bien présent·e à la fin de l'intervention. Le bouton Terminer n'apparaît donc qu'après avoir scanné le QR de la famille.
\end{important}

\begin{bonasavoir}
Vous pouvez commencer la garde depuis cette page, et la terminer en scannant le QR de la famille — même avec un autre téléphone. Ce n'est pas un problème.
\end{bonasavoir}

% ============================================================
\section{Écrire vos heures vous-même}
\label{sec:saisie-manuelle}
% ============================================================

Utilisez cette méthode si vous avez oublié de pointer, ou si vous n'avez pas pu scanner le QR Code.

\begin{etapes}{Pour accéder à l'écran de saisie}
\begin{enumerate}[itemsep=8pt]
    \item Depuis l'accueil, appuyez sur \textbf{Saisir mes heures}.
\end{enumerate}
\end{etapes}

\photo{0.7}{images/intervenant-06-saisie.png}

\begin{etapes}{Pour remplir l'écran}
\begin{enumerate}[itemsep=8pt, start=2]
    \item Choisissez le type d'intervention : \textbf{Garde d'enfants} ou \textbf{Ménage}.
    \item Choisissez la \textbf{famille} dans la liste.
    \item Choisissez la \textbf{date}.
    \item Choisissez l'\textbf{heure de début}.
    \item Choisissez l'\textbf{heure de fin}.
    \item Si vous avez conduit avec un enfant, écrivez le nombre de \textbf{kilomètres parcourus}.
    \item Appuyez sur \textbf{Valider}.
\end{enumerate}
\end{etapes}

\begin{siboite}{l'application refuse votre saisie}
Un message rouge explique pourquoi. Les raisons les plus courantes :
\begin{itemize}[itemsep=4pt]
    \item Vous avez déjà une intervention à cette heure-là ce jour-là.
    \item La date que vous avez choisie n'est pas encore arrivée.
    \item Le mois est déjà \textbf{terminé} : vous ne pouvez plus le changer. Contactez-nous.
\end{itemize}
\end{siboite}

% ============================================================
\section{Voir et changer vos heures}
\label{sec:mes-heures}
% ============================================================

\begin{etapes}{Pour accéder à la liste de vos heures}
\begin{enumerate}[itemsep=8pt]
    \item Depuis le menu en haut, appuyez sur \textbf{Mes heures}.
\end{enumerate}
\end{etapes}

\photo{1}{images/intervenant-07-mes-heures.png}

Vous voyez la liste de toutes vos interventions, classées par mois. Le mois en cours s'affiche déjà ouvert.

\begin{siboite}{vous vous êtes trompé·e}
\textbf{\textbf{$\rightarrow$} Appuyez sur la ligne concernée} pour changer les horaires ou la supprimer.
\end{siboite}

\begin{siboite}{vous ne pouvez plus changer une ligne}
Le mois est déjà terminé, ou vous avez déjà signé votre relevé (section~\ref{sec:releve}). \textbf{\textbf{$\rightarrow$} Contactez-nous} au \textbf{\contacttel} pour une correction.
\end{siboite}

% ============================================================
\section{Votre planning}
\label{sec:planning}
% ============================================================

\begin{etapes}{Pour voir votre planning de la semaine}
\begin{enumerate}[itemsep=8pt]
    \item Depuis le menu en haut, appuyez sur \textbf{Planning}.
\end{enumerate}
\end{etapes}

\photo{1}{images/intervenant-08-planning.png}

Chaque jour de la semaine est affiché avec les interventions prévues (famille, horaires). Le jour d'aujourd'hui est mis en évidence.

\begin{bonasavoir}
En appuyant sur une intervention prévue, une petite fenêtre s'ouvre avec un bouton \textbf{Saisir}. Cela vous évite de tout réécrire à la main.
\end{bonasavoir}

% ============================================================
\section{Signer votre relevé -- pour être payé·e}
\label{sec:releve}
% ============================================================

\begin{important}[Une étape indispensable]
\Large\textbf{Chaque mois, vous devez signer votre relevé pour être payé·e.}\\[6pt]
\normalsize Sans signature, l'administration ne peut pas valider votre paie.\\[4pt]
\textcolor{red!70!black}{\textbf{Une fois signé, vous ne pourrez plus changer les heures de ce mois.}} Vérifiez bien avant de signer.
\end{important}

\subsection{Comment y accéder}

\begin{etapes}{}
\begin{enumerate}[itemsep=8pt]
    \item Depuis le menu en haut, appuyez sur \textbf{Mes relevés}.
    \item Choisissez le \textbf{mois}.
    \item Choisissez le \textbf{type} : Garde d'enfants ou Ménage.
\end{enumerate}
\end{etapes}

\photo{1}{images/intervenant-09-releve-signer.png}

\subsection{Vous vous êtes trompé·e dans une ligne ?}

\begin{etapes}{Pour changer une heure avant de signer}
\begin{enumerate}[itemsep=8pt]
    \item Appuyez sur la case qui contient déjà des heures.
    \item Une petite fenêtre s'ouvre : changez l'heure de début et l'heure de fin.
    \item Appuyez sur l'icône \textbf{\checkmark} (la coche) pour enregistrer.
    \item Appuyez sur \textbf{Fermer}.
\end{enumerate}
\end{etapes}

\begin{siboite}{vous voulez supprimer une intervention}
\textbf{\textbf{$\rightarrow$} Dans la même petite fenêtre}, appuyez sur l'icône \textbf{corbeille}.
\end{siboite}

\begin{siboite}{une journée est vide et vous voulez y ajouter une intervention}
Les cases vides ne s'ouvrent pas au clic. \textbf{\textbf{$\rightarrow$} Utilisez « Saisir mes heures »} (section~\ref{sec:saisie-manuelle}), puis revenez sur cette page : la ligne apparaîtra automatiquement.
\end{siboite}

\subsection{Heures faites en dehors de l'association}

Si vous avez aussi travaillé pour d'autres employeurs ce mois-ci (hors association), vous pouvez le signaler tout en bas de la page.

\begin{etapes}{}
\begin{enumerate}[itemsep=8pt]
    \item Tout en bas de la page, écrivez le nombre d'\textbf{heures} effectuées ailleurs.
    \item Appuyez sur \textbf{Sauvegarder heures hors Chaudoudoux}.
\end{enumerate}
\end{etapes}

\subsection{La dernière étape : signer}

\begin{etapes}{Une fois que tout est correct}
\begin{enumerate}[itemsep=8pt]
    \item Relisez une dernière fois toutes vos heures.
    \item Appuyez sur \textbf{Signer}.
    \item Une fenêtre de confirmation apparaît : appuyez de nouveau sur \textbf{Signer}.
\end{enumerate}
\end{etapes}

\begin{bonasavoir}
C'est terminé ! Votre relevé signé vous est automatiquement envoyé par e-mail, au format PDF (un fichier que vous pouvez ouvrir, imprimer ou garder).
\end{bonasavoir}

\begin{siboite}{un message « Ce mois est clôturé » apparaît}
Le délai pour signer ce mois est passé. \textbf{\textbf{$\rightarrow$} Contactez-nous} au \textbf{\contacttel} : nous réglerons cela ensemble.
\end{siboite}

% ============================================================
\section{Votre profil -- vos informations personnelles}
\label{sec:profil}
% ============================================================

\begin{etapes}{Pour accéder à votre profil}
\begin{enumerate}[itemsep=8pt]
    \item Depuis le menu en haut, appuyez sur \textbf{Mon profil}.
\end{enumerate}
\end{etapes}

\photo{0.8}{images/intervenant-10-profil.png}

Vous y voyez votre nom, votre numéro de téléphone, votre e-mail, votre téléphone et votre adresse.

\begin{important}[À vérifier de temps en temps]
Votre \textbf{e-mail} doit être juste : c'est là que vous recevez vos relevés et vos codes en cas de mot de passe oublié.\\[4pt]
Votre \textbf{téléphone} doit être juste : c'est ce numéro qui sert pour le pointage sans connexion.
\end{important}

% ============================================================
\section{Si quelque chose ne va pas}
\label{sec:depannage}
% ============================================================

\begin{center}
\textit{Vous trouverez ici les questions les plus fréquentes. Si votre problème n'y est pas, appelez-nous au \textbf{\contacttel} : c'est toujours plus simple d'en parler.}
\end{center}

\vspace{0.3cm}

\begin{siboite}{vos heures n'apparaissent pas}
Avez-vous bien appuyé sur \textbf{Terminer} à la fin de votre intervention (section~\ref{sec:qrcode}) ? Un pointage non terminé n'est jamais enregistré.
\end{siboite}

\begin{siboite}{vous ne pouvez plus changer une heure}
Le mois est terminé, ou vous avez déjà signé votre relevé. \textbf{\textbf{$\rightarrow$} Contactez-nous} pour une correction.
\end{siboite}

\begin{siboite}{vous n'avez pas reçu l'e-mail pour retrouver votre mot de passe}
Regardez dans vos \textbf{spams / indésirables}. Vérifiez que votre e-mail est correct dans \textbf{Mon profil} (section~\ref{sec:profil}). Toujours rien après 5 minutes ? Réessayez une nouvelle demande.
\end{siboite}

\begin{siboite}{la mention « Famille occasionnelle » s'affiche}
C'est normal : cette famille n'est pas dans votre planning habituel. Vous pouvez tout de même enregistrer vos heures.
\end{siboite}

\begin{siboite}{le QR Code ne veut pas se scanner}
Approchez ou éloignez votre téléphone, ou allumez la lumière. Toujours bloqué·e ? Utilisez la saisie manuelle (section~\ref{sec:saisie-manuelle}).
\end{siboite}

\begin{siboite}{votre compte est bloqué}
Après plusieurs mots de passe incorrects, l'accès se bloque \textbf{quelques minutes} par sécurité. Attendez un peu, puis réessayez, ou utilisez \textbf{« Mot de passe oublié ? »}.
\end{siboite}

% ============================================================
\section{Pour résumer -- votre mois en 4 étapes}
\label{sec:resume}
% ============================================================

\begin{etapes}{Ce qu'il faut faire, et quand}
\begin{enumerate}[itemsep=10pt]
    \item \textbf{À chaque intervention} : scannez le QR Code (section~\ref{sec:qrcode}). Appuyez sur \textbf{Démarrer} en arrivant, sur \textbf{Terminer} en partant.
    \item \textbf{En cas d'oubli} : utilisez la saisie manuelle (section~\ref{sec:saisie-manuelle}).
    \item \textbf{À la fin du mois} : allez dans \textbf{Mes relevés} (section~\ref{sec:releve}), vérifiez toutes les lignes, puis \textbf{signez}.
    \item \textbf{De temps en temps} : vérifiez votre e-mail et votre téléphone dans \textbf{Mon profil} (section~\ref{sec:profil}).
\end{enumerate}
\end{etapes}

% ============================================================
\section*{Une question ? Nous sommes là}
% ============================================================

\begin{center}
\begin{tcolorbox}[colback=blue!4!white, colframe=blue!55!black, width=0.85\textwidth, arc=2mm]
\centering
\Large \textbf{N'hésitez jamais à nous appeler.}\\[8pt]
\normalsize
Téléphone : \textbf{\contacttel}\\
E-mail : \textbf{\contactmail}
\end{tcolorbox}
\end{center}

\end{document}