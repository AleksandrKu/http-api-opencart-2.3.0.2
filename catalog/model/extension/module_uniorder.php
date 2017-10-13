<?php
class ModelExtensionModuleUniorder extends Model {
    public function getTokenByModuleName($code) {
        $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "module WHERE code = '" . $code . "'");
        if ($query->row) {
            $return = $query->row['setting'];
            $return = json_decode($return, true);

            for ($i = 1; $i < 4; $i++) {
                if (!empty($return['module_description'][$i]['title'])) {
                    return $return['module_description'][$i]['title'];  //
                    break;
                }
            }
           /* return $return['module_description']['1']['title'];*/

        } else {
            return "No Module with code uniorder!";
        }
    }
}