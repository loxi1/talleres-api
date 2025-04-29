<?php 
header('Content-Type: application/json');  
// Verifica el método de solicitud
$request_method = $_SERVER['REQUEST_METHOD'];

switch ($request_method) {
    case 'POST':
        $data = array();
        $rta = array('code' => 0,'msn' => 'No se encontraron datos.','data' => $data);
        $param = json_decode(file_get_contents('php://input'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Manejo de error: JSON no válido
            echo json_encode($rta);
            exit;
        }
        print_r($param);
        $codigo = isset($param['codigo']) ? (!empty($param['codigo']) ? $param['codigo'] : null) :null;
        $pwd = isset($param['password']) ? (!empty($param['password']) ? $param['password'] : null) :null;

        if(!empty($codigo) || !empty($pwd)) {
            echo json_encode($rta);
            exit;
        }

        $select_sql_sybase = "SELECT identificador, codigo, datos FROM usuario_timbrado WHERE codigo='".$codigo."' AND estado = 'ACTIVO'"; 
        //print_r($select_sql_sybase);
        //echo "<pre>";
        //print_r($select_sql_sybase); // Muestra el contenido de la variable rs_data_sybase
        //echo "</pre>";
        // Ejecuta la consulta y guarda los resultados en la variable rs_data_sybase
        sc_lookup(rs_data_sybase, $select_sql_sybase);
        $rta = array('error' => 'No se encontraron datos.');
        // Verifica si se obtuvieron resultados
        if (isset({rs_data_sybase}) && count({rs_data_sybase}) > 0) {
            // Inicializa un array para almacenar los datos
            
            // Recorre los resultados
            foreach ({rs_data_sybase} as $row) {
                // Cada fila será agregada al array de rta
                $data[] = array(
                    'identificador' => $row[0], // primer campo
                    'codigo' => $row[1],         // segundo campo
                    'datos' => mb_convert_encoding($row[2], 'UTF-8', 'CP850')
                );
            }				
        }
        

        // Convierte el array a formato JSON y lo imprime
        echo json_encode($rta);
        exit;

    break;
    
}