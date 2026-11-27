<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Modèle des visites : couvre la table SQL « Visite » et ses tables associées
 * « Presenter » (médicaments présentés), « Offrir » (échantillons offerts),
 * avec les jointures vers Practicien, Specialite, Visiteur et Medicament.
 * Propose la consultation des visites (par visiteur ou par praticien), la création
 * d'un rapport de visite complet, le passage au statut acceptée/refusée et la suppression
 * d'une visite tant qu'elle ne comporte ni bilan ni échantillon.
 * La création d'une visite est atomique (transaction visite + presenter + offrir).
 */
class Visite {
    private $db;

    /**
     * Initialise la connexion à la base de données utilisée par les méthodes du modèle.
     */
    public function __construct() {
        $this->db = Database::getConnection();
    }

    /**
     * Récupère toutes les visites effectuées par un visiteur, de la plus récente à la plus ancienne.
     * @param int $idVis Identifiant du visiteur.
     * @return array Liste des visites enrichie du praticien, de sa spécialité et du visiteur.
     */
    public function getVisitesByVisiteur($idVis) {
        $sql = "SELECT v.*, p.nom_par, p.prenom_par, s.nom_spe, vis.nom_vis, vis.prenom_vis 
                FROM Visite v
                JOIN Practicien p ON (v.Id_pra = p.Id_pra AND v.id_spe = p.id_spe)
                JOIN Specialite s ON p.id_spe = s.id_spe
                JOIN Visiteur vis ON v.id_vis_1 = vis.id_vis
                WHERE v.id_vis_1 = :id_vis
                ORDER BY v.date_vis DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_vis' => $idVis]);
        return $stmt->fetchAll();
    }

    /**
     * Récupère une visite par son identifiant, avec toutes ses données associées.
     * @param int $idVisite Identifiant de la visite.
     * @return array|false La visite (praticien, spécialité, visiteur), enrichie des tableaux
     *                     « echantillons » (Offrir) et « medicaments_presentes » (Presenter),
     *                     ou false si elle n'existe pas.
     * Effet de bord : deux requêtes supplémentaires sont lancées uniquement si la visite existe.
     */
    public function getVisiteById($idVisite) {
        $sql = "SELECT v.*, p.nom_par, p.prenom_par, p.rue_par, p.codePostal, p.ville_par, s.nom_spe, vis.nom_vis, vis.prenom_vis 
                FROM Visite v
                JOIN Practicien p ON (v.Id_pra = p.Id_pra AND v.id_spe = p.id_spe)
                JOIN Specialite s ON p.id_spe = s.id_spe
                JOIN Visiteur vis ON v.id_vis_1 = vis.id_vis
                WHERE v.id_vis = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idVisite]);
        $visite = $stmt->fetch();

        if ($visite) {
            // Échantillons offerts lors de la visite (table Offrir) avec le nom commercial du médicament.
            $stmtOff = $this->db->prepare("SELECT o.qteEchantillon_med, m.nomCommercial_med 
                                           FROM Offrir o 
                                           JOIN Medicament m ON o.id_med = m.id_med 
                                           WHERE o.id_vis = :id");
            $stmtOff->execute(['id' => $idVisite]);
            $visite['echantillons'] = $stmtOff->fetchAll();

            // Médicaments simplement présentés au praticien (table Presenter), sans quantité.
            $stmtPres = $this->db->prepare("SELECT m.nomCommercial_med 
                                            FROM Presenter pr 
                                            JOIN Medicament m ON pr.id_med = m.id_med 
                                            WHERE pr.id_vis = :id");
            $stmtPres->execute(['id' => $idVisite]);
            $visite['medicaments_presentes'] = $stmtPres->fetchAll();
        }
        return $visite;
    }

    /**
     * Liste tous les praticiens approuvés et actifs, avec leur spécialité, triés par nom puis prénom.
     * Condition métier : seuls les comptes validés (statut_validation = 'actif') et
     * ouverts (statut_compte = 'actif') sont proposables lors d'une visite.
     * @return array Liste des praticiens éligibles.
     */
    public function getAllPracticiens() {
        $sql = "SELECT p.id_spe, p.Id_pra, p.note_par, p.nom_par, p.prenom_par, p.rue_par, p.codePostal, p.ville_par, s.nom_spe 
                FROM Practicien p 
                JOIN Specialite s ON p.id_spe = s.id_spe
                WHERE p.statut_validation = 'approuve' AND p.statut_compte = 'actif'
                ORDER BY p.nom_par, p.prenom_par";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    /**
     * Récupère les visites d'un praticien, de la plus récente à la plus ancienne.
     * Jointures : visiteur ayant effectué la visite et spécialité du praticien.
     * @param int $idSpe Identifiant de la spécialité du praticien.
     * @param int $idPra Identifiant du praticien.
     * @return array Liste des visites enrichie du visiteur et de la spécialité.
     */
    public function getVisitesByPracticien($idSpe, $idPra) {
        $sql = "SELECT v.*, vis.nom_vis, vis.prenom_vis, s.nom_spe 
                FROM Visite v
                JOIN Visiteur vis ON v.id_vis_1 = vis.id_vis
                JOIN Specialite s ON v.id_spe = s.id_spe
                WHERE v.id_spe = :id_spe AND v.Id_pra = :id_pra
                ORDER BY v.date_vis DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_spe' => $idSpe, 'id_pra' => $idPra]);
        return $stmt->fetchAll();
    }

    /**
     * Liste les visites refusées d'un visiteur, de la plus récente à la plus ancienne.
     * @param int $idVis Identifiant du visiteur.
     * @return array Liste des visites dont le statut vaut 'refusee'.
     */
    public function getVisitesRefuseesByVisiteur($idVis) {
        $sql = "SELECT v.id_vis, v.date_vis, v.motif_vis, p.nom_par, p.prenom_par, s.nom_spe
                FROM Visite v
                JOIN Practicien p ON (v.Id_pra = p.Id_pra AND v.id_spe = p.id_spe)
                JOIN Specialite s ON p.id_spe = s.id_spe
                WHERE v.id_vis_1 = :id_vis AND v.statut_vis = 'refusee'
                ORDER BY v.date_vis DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_vis' => $idVis]);
        return $stmt->fetchAll();
    }

    /**
     * Passe une visite au statut « acceptée » ou « refusée ».
     * Transition unique : seule une visite encore 'en_attente' peut changer de statut.
     * @param int $idVisite Identifiant de la visite.
     * @param int $idSpe Spécialité du praticien concerné (double clé de Visite).
     * @param int $idPra Identifiant du praticien concerné (double clé de Visite).
     * @param string $statut Nouveau statut : 'acceptee' ou 'refusee' (toute autre valeur => false).
     * @return bool true si une ligne a bien été mise à jour, false sinon.
     * Effet de bord : UPDATE de la colonne statut_vis dans la table Visite.
     */
    public function setStatutVisite($idVisite, $idSpe, $idPra, $statut) {
        if (!in_array($statut, ['acceptee', 'refusee'], true)) {
            return false;
        }
        $stmt = $this->db->prepare("UPDATE Visite
                                    SET statut_vis = :statut
                                    WHERE id_vis = :id_vis
                                      AND id_spe = :id_spe
                                      AND Id_pra = :id_pra
                                      AND statut_vis = 'en_attente'");
        return $stmt->execute([
            'statut' => $statut,
            'id_vis' => $idVisite,
            'id_spe' => $idSpe,
            'id_pra' => $idPra
        ]) && $stmt->rowCount() > 0;
    }

    public function getMedicaments() {
        return $this->db->query("SELECT * FROM Medicament ORDER BY nomCommercial_med")->fetchAll();
    }

    public function createVisite($dateVis, $motifVis, $bilanVis, $idSpe, $idPra, $idVisiteur, $medsPresentes, $echantillonsOfferts) {
        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare("INSERT INTO Visite (date_vis, motif_vis, bilan_vis, id_spe, Id_pra, id_vis_1) VALUES (:date_vis, :motif_vis, :bilan_vis, :id_spe, :id_pra, :id_vis_1)");
            $stmt->execute([
                'date_vis' => $dateVis,
                'motif_vis' => $motifVis,
                'bilan_vis' => $bilanVis,
                'id_spe' => $idSpe,
                'id_pra' => $idPra,
                'id_vis_1' => $idVisiteur
            ]);
            $idVisite = $this->db->lastInsertId();

            if (!empty($medsPresentes)) {
                $stmtPres = $this->db->prepare("INSERT INTO Presenter (id_vis, id_med) VALUES (:id_vis, :id_med)");
                foreach ($medsPresentes as $idMed) {
                    $stmtPres->execute(['id_vis' => $idVisite, 'id_med' => $idMed]);
                }
            }

            if (!empty($echantillonsOfferts)) {
                $stmtOff = $this->db->prepare("INSERT INTO Offrir (id_vis, id_med, qteEchantillon_med) VALUES (:id_vis, :id_med, :qte)");
                foreach ($echantillonsOfferts as $idMed => $qte) {
                    if ($qte > 0) {
                        $stmtOff->execute(['id_vis' => $idVisite, 'id_med' => $idMed, 'qte' => (string)$qte]);
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function deleteVisite($idVisite, $idVisiteur) {
        $visite = $this->getVisiteById($idVisite);
        if (!$visite || $visite['id_vis_1'] != $idVisiteur) {
            return false;
        }
        if (!empty($visite['bilan_vis']) || !empty($visite['echantillons'])) {
            return false;
        }
        $stmt = $this->db->prepare("DELETE FROM Visite WHERE id_vis = :id");
        return $stmt->execute(['id' => $idVisite]);
    }
}
