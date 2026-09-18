/***************************/
/* 
// Tinder (BD MySQL) 
// @MB Le 20/09/26 13:17:00
*/
/***************************/

-- First we create the database

CREATE DATABASE TINDER22
DEFAULT CHARACTER SET UTF8    -- Tous les formats de caractères
DEFAULT COLLATE utf8_general_ci ; --

-- SHOW VARIABLES;        -- Voir les paramètres de la BD

-- Flush / Init all privileges
FLUSH PRIVILEGES;

-- Now we create the Database

-- phpMyAdmin SQL Dump
-- version 4.4.10
-- http://www.phpmyadmin.net
--
-- Client :  localhost:8889
-- Généré le :  Mer 20 septembre 2026 à 16:23
-- Version du serveur :  5.5.42
-- Version de PHP :  7.0.0

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

--
-- Base de données :  TINDER22
--
USE TINDER22;

-- ------------------------------------------
--
-- Structure de la table USER
--

/*=========================================*/
/* Table : USER                            */
/*=========================================*/
create table USER
(
   idUser int(10) not null auto_increment,   -- PK
   idGenr int(3) not null,                   -- FK
   nomEUser varchar(50),
   prenomUser varchar(50),
   photo varchar(50),
   age int(8),
   biographie varchar(150),
   primary key (idUser)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*=========================================*/
/* Index : USER_FK                         */
/*=========================================*/
create index USER_FK on USER
(
   idUser
);

-- ------------------------------------------
--
-- Structure de la table GENRE
--

/*=========================================*/
/* Table : GENRE                           */
/*=========================================*/
create table GENRE
(
   idGenr int(3) not null auto_increment,   -- PK
   libGenr varchar(30),
   primary key (idGenr)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*=========================================*/
/* Index : GENRE_FK                        */
/*=========================================*/
create index GENRE_FK on GENRE
(
   idGenr
);

-- ------------------------------------------
--
-- Structure de la table LIKES (TJ reflexive)
--

/*=========================================*/
/* Table : LIKES                           */
/*=========================================*/
create table LIKES
(
   idUserL1 int(10) not null,  -- PK, FK
   idUserL2 int(10) not null,  -- PK, FK
   likeL1 bool,
   primary key (idUserL1, idUserL2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*=========================================*/
/* Index : LIKES_FK                        */
/*=========================================*/
create index LIKES_FK on LIKES
(
   idUserL1,
   idUserL2
);

-- ------------------------------------------
--
-- Structure de la table MATCHS (TJ reflexive)
--

/*=========================================*/
/* Table : MATCHS                          */
/*=========================================*/
create table MATCHS
(
   idUserM1 int(10) not null,  -- PK, FK
   idUserM2 int(10) not null,  -- PK, FK
   primary key (idUserM1, idUserM2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*=========================================*/
/* Index : MATCHS_FK                       */
/*=========================================*/
create index MATCHS_FK on MATCHS
(
   idUserM1,
   idUserM2
);

-- ------------------------------------------
--
-- Structure de la table COMMENTS (TJ reflexive)
--

/*=========================================*/
/* Table : COMMENTS                        */
/*=========================================*/
create table COMMENTS
(
   idUserC1 int(10) not null,  -- PK, FK
   idUserC2 int(10) not null,  -- PK, FK
   libComment varchar(300),
   primary key (idUserC1, idUserC2)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

/*=========================================*/
/* Index : COMMENTS_FK                     */
/*=========================================*/
create index COMMENTS_FK on COMMENTS
(
   idUserC1,
   idUserC2
);


-- --------------------------------------------------------------------
--
-- Contraintes pour les tables exportées
--

-- --------------------------------------------------------------------

alter table USER add constraint FK_ASSOCIATION_0 foreign key (idGenr)
      references GENRE (idGenr) on delete cascade on update cascade;

-- --------------------------------------------------------------------

alter table LIKES add constraint FK_LIKES foreign key (idUserL1)
      references USER (idUser) on delete cascade on update cascade;

alter table LIKES add constraint FK_LIKES2 foreign key (idUserL2)
      references USER (idUser) on delete cascade on update cascade;

-- --------------------------------------------------------------------

alter table MATCHS add constraint FK_MATCHS foreign key (idUserM1)
      references USER (idUser) on delete cascade on update cascade;

alter table MATCHS add constraint FK_MATCHS2 foreign key (idUserM2)
      references USER (idUser) on delete cascade on update cascade;

-- --------------------------------------------------------------------

alter table COMMENTS add constraint FK_COMMENTS foreign key (idUserC1)
      references USER (idUser) on delete cascade on update cascade;

alter table COMMENTS add constraint FK_COMMENTS2 foreign key (idUserC2)
      references USER (idUser) on delete cascade on update cascade;

-- --------------------------------------------------------------------

-- --------------------------------------------------------------------
-- --------------------------------------------------------------------
