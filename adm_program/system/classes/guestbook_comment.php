<?php
/******************************************************************************
 * Klasse fuer Datenbanktabelle adm_guestbook_comments
 *
 * Copyright    : (c) 2004 - 2008 The Admidio Team
 * Homepage     : http://www.admidio.org
 * Module-Owner : Markus Fassbender
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Diese Klasse dient dazu ein Gaestebuchkommentarobjekt zu erstellen. 
 * Eine Gaestebuchkommentar kann ueber diese Klasse in der Datenbank verwaltet werden
 *
 * Das Objekt wird erzeugt durch Aufruf des Konstruktors und der Uebergabe der
 * aktuellen Datenbankverbindung:
 * $guestbook_comment = new GuestbookComment($g_db);
 *
 * Mit der Funktion readData($gbc_id) kann nun der gewuenschte Gaestebuchkommentar ausgelesen
 * werden.
 *
 * Folgende Funktionen stehen nun zur Verfuegung:
 *
 * clear()                - Die Klassenvariablen werden neu initialisiert
 * setArray($field_arra)  - uebernimmt alle Werte aus einem Array in das Field-Array 
 * setValue($field_name, $field_value) - setzt einen Wert fuer ein bestimmtes Feld
 * getValue($field_name)  - gibt den Wert eines Feldes zurueck
 * save()                 - Gaestebuchkommentar wird mit den geaenderten Daten in die Datenbank
 *                          zurueckgeschrieben bwz. angelegt
 * delete()               - Der aktuelle Gaestebuchkommentar wird aus der Datenbank geloescht
 *
 *****************************************************************************/

require_once(SERVER_PATH. "/adm_program/system/classes/table_access.php");

class GuestbookComment extends TableAccess
{
    // Konstruktor
    function GuestbookComment(&$db, $gbc_id = 0)
    {
        $this->db            =& $db;
        $this->table_name     = TBL_GUESTBOOK_COMMENTS;
        $this->column_praefix = "gbc";
        
        if($gbc_id > 0)
        {
            $this->readData($gbc_id);
        }
        else
        {
            $this->clear();
        }
    }

    // Termin mit der uebergebenen ID aus der Datenbank auslesen
    function readData($gbc_id)
    {
        $tables    = TBL_GUESTBOOK;
        $condition = "       gbc_gbo_id = gbo_id 
                         AND gbc_id     = $gbc_id ";
        parent::readData($gbc_id, $condition, $tables);
    }
    
    // interne Methode, die bei setValue den uebergebenen Wert prueft
    // und ungueltige Werte auf leer setzt
    // die Methode wird innerhalb von setValue() aufgerufen
    function setValue($field_name, $field_value)
    {
        if(strlen($field_value) > 0)
        {
            if($field_name == "gbc_email")
            {
                if (!isValidEmailAddress($field_value))
                {
                    // falls die Email ein ungueltiges Format aufweist wird sie einfach auf null gesetzt
                    $field_value = "";
                }
            }
        }
        parent::setValue($field_name, $field_value);
    }
    
    // interne Funktion, die Defaultdaten fur Insert und Update vorbelegt
    // die Funktion wird innerhalb von save() aufgerufen
    function save()
    {
        global $g_current_organization, $g_current_user;
        
        if($this->new_record)
        {
            $this->setValue("gbc_timestamp", date("Y-m-d H:i:s", time()));
            $this->setValue("gbc_usr_id", $g_current_user->getValue("usr_id"));
            $this->setValue("gbc_org_id", $g_current_organization->getValue("org_id"));
            $this->setValue("gbc_ip_address", $_SERVER['REMOTE_ADDR']);
        }
        else
        {
            $this->setValue("gbc_last_change", date("Y-m-d H:i:s", time()));
            $this->setValue("gbc_usr_id_change", $g_current_user->getValue("usr_id"));
        }
        parent::save();
    }   
}
?>