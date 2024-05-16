<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class facturacion_electronica extends CI_Controller {

	public function __construct() {
		parent::__construct();
		$this->load->helper(array('download', 'file', 'url', 'html', 'form'));
		//$this->folder = base_url().'uploads/';
		$this->folder = 'uploads/';
		$this->folder_path= 'uploads';
		$this->load->library('upload');
		$this->load->library('PHPExcel');
	}

	public function index() {
		
		$cambio['fecha'] = '26-08-2016';
		$cambio['titulo'] = 'Notas de crédito';
		$cambio['descripcion'] = 'Lectura de notas de crédito junto con las facturas, ademas esto incluye una nueva columna (Nota de crédito) que indica, si una factura ha sido afetada por una nota de crédito'; 
		$data['cambios'][0] = $cambio;

		$cambio['fecha'] = '26-08-2016';
		$cambio['titulo'] = 'Seguro Campecino';
		$cambio['descripcion'] = 'Nueva fila en el informe (Seguro campecino), se mantendrá en 0.00 en caso de que la factura no presente este atributo adicional'; 
		$data['cambios'][1] = $cambio;

		$cambio['fecha'] = '26-08-2016';
		$cambio['titulo'] = 'Impuesto Redimible a las Botellas Plásticas no retornables (IRBPNR)';
		$cambio['descripcion'] = 'Nueva columna agregada, este impuesto unicamente se calculará cuando la factura cuente con este atributo'; 
		$data['cambios'][2] = $cambio;

		$cambio['fecha'] = '15-05-2024';
		$cambio['titulo'] = 'IVA 15%';
		$cambio['descripcion'] = 'Se implementó el IVA de 15%'; 
		$data['cambios'][3] = $cambio;
		
		$data['main_content'] = 'facturacion_electronica/form_generar_informe';
		$this->load->view('includes/template', $data);
	}

	function verificar_espacio_ocupado() {
		$size = 0;
	    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator('upload')) as $file){
	        $size+=$file->getSize();
	    }
	    return (($size/1000)/1000);
	}
	

	function subida_multiple() {
		
		//$data['idempresa'] = $this->session->userdata('idempresa');
		$directorio_origen = $this->folder_path;
		
		if (!file_exists($this->folder_path)) {
			mkdir($this->folder_path, 777, true);
		} 
		
		$config['upload_path'] = $directorio_origen.'/';
		$config['allowed_types'] = '*';
		$config['max_size']      = '2048';
		$config['overwrite']     = FALSE;
		$this->load->library('upload');

		$files = $_FILES;
		$cpt = count($_FILES['userfile']['name']);
		$nombre_pull='';
		for($i=0; $i < $cpt; $i++){

			$nombre_pull = ''.rand(1000000000,9999999999);
			$_FILES['userfile']['name'] = 'pull_'.$nombre_pull.'.zip';
			$_FILES['userfile']['type'] = $files['userfile']['type'][$i];
			$_FILES['userfile']['tmp_name'] = $files['userfile']['tmp_name'][$i];
			$_FILES['userfile']['error'] = $files['userfile']['error'][$i];
			$_FILES['userfile']['size'] = $files['userfile']['size'][$i];    

			$this->upload->initialize($config);
			$this->upload->do_upload();

		}

		$path_de_lectura = $directorio_origen."/".$nombre_pull;
		if (!file_exists($directorio_origen."/".$nombre_pull)) {
			mkdir($directorio_origen."/".$nombre_pull, 0777, true);
		} 
		
		$zip = new ZipArchive;
		//echo 'Archivo: pull_'.$directorio_origen."/".$nombre_pull.'.zip<br>';
		if ($zip->open($directorio_origen.'/pull_'.$nombre_pull.'.zip') === TRUE) {
			$zip->extractTo($path_de_lectura);
			$zip->close();
			//echo 'Extraido';
		} else {
			//echo 'fallo';
		}
		
		$this->leer_archivos_directorio($path_de_lectura, $nombre_pull);
		$this->borrar_carpeta($directorio_origen.'/pull_'.$nombre_pull.'.zip',$path_de_lectura);
	}   

	function leer_archivos_directorio($directorio_origen, $nombre_pull){
		
		$directorio = opendir($directorio_origen);
		$filas = 1;
		//echo 'Lista de facturas <br><br><br>';
		$facturas=null;
		$notas_credito=null;
		$archivos = null;
		$i=0;
		$fallidos=null;
		$i_f=0;
		
		while ($archivo = readdir($directorio))  {
			//obtenemos un archivo y luego otro sucesivamente
			//echo '<pre>'.var_export(!is_dir($archivo), true).'</pre>';exit;
		    if (!is_dir($archivo)){
				//verificamos si es o no un directorio
		    	//echo  $filas.'.- '. $archivo . "<br />";
		    	$notas_credito[$i] = $this->leer_nota_credito($directorio_origen.'/'.$archivo);
		    	$archivos[$i]= $directorio_origen.'/'.$archivo;
		    	
		    	$filas++;
		    	$i++;
		    }
		    
		}
		//echo '<pre>'.var_export($archivos, true).'</pre>';exit;
		$i=0;

		if (isset($archivos) && $archivos != NULL) {
			foreach ($archivos as $value) {
				$facturas[$i] = $this->lector($value, $notas_credito);
				if ($facturas[$i]==null) {
						$fallidos[$i_f] = $value;
						$i_f++;
					}
				$i++;
			}

			//var_dump($facturas);
			//var_dump($notas_credito);
			$guardar = false;
			
			if (count($fallidos) > 0) {
				
				$ruta_excel = $this->generar_excel($facturas,true,$nombre_pull);
				$this->comprimir_en_zip($fallidos,$nombre_pull,$ruta_excel);
			}else{
				
				$this->generar_excel($facturas,false,$nombre_pull);
			}

			/*
				VERIFICA SI LA CARPETA QUE AUN CONSERVA ARCHIVOS TIENE MAS DE 50MB
				DE SER EL CASO ENVIA UN EMAIL AL ADMINISTRADOR PARA NOTIFICARLE
			*/
			$espacio_memoria = $this->verificar_espacio_ocupado();
			if ($espacio_memoria >= 50) {
				$this->enviar_email_alerta();
				$this->borrar_archivos_directorio('upload');
			}
		}else{
			echo 'no se ha cargado el archivo ZIP en '. site_url()."uploads/";
		}

	}

	function get_nombre_from_path($path){
		$datos= explode('/', $path);
		$nombre = explode('.', $datos[count($datos)-1]);
		$retorno = array('nombre' => $nombre[0],'ext' => $nombre[1] );
		return $retorno;
	}

	function comprimir_en_zip($archivos,$nombre_pull,$ruta_excel){

	   	$zip = new ZipArchive;
	   	$zip->open("upload/documentos_fallidos_".$nombre_pull.".zip",ZipArchive::CREATE);
	   	$i = 1;
	   	if ($archivos!=null) {
	   			
		   	foreach ($archivos as $value) {
		   		//echo "<br>Mijin: ".$value;
		   		$nombres_archivo = $this->get_nombre_from_path($value);
		   		$zip->addFile($value,'Fallido_'.$i.'.'.$nombres_archivo['ext']);
		   		$i++;

		   	}
	    }
	   	$zip->close();
	   	$data['ubicacion'] = "upload/documentos_fallidos_".$nombre_pull.".zip";
	   	$data['ruta_excel'] = $ruta_excel;
		$data['cantidad'] = $i;

		$cambio['fecha'] = '26-08-2016';
		$cambio['titulo'] = 'Notas de crédito';
		$cambio['descripcion'] = 'Lectura de notas de crédito junto con las facturas, ademas esto incluye una n ueva columna (Nota de crédito) que indica, si una factura ha sido afetada por una nota de crédito'; 
		$data['cambios'][0] = $cambio;
		$cambio['fecha'] = '26-08-2016';
		$cambio['titulo'] = 'Seguro Campecino';
		$cambio['descripcion'] = 'Nueva fila en el informe (Seguro campecino), se mantendrá en 0.00 en caso de que la factura no presente este atributo adicional'; 
		$data['cambios'][1] = $cambio;
		$cambio['fecha'] = '26-08-2016';
		$cambio['titulo'] = 'Impuesto Redimible a las Botellas Plásticas no retornables (IRBPNR)';
		$cambio['descripcion'] = 'Nueva columna agregada, este impuesto unicamente se calculará cuando la factura cuente con este atributo'; 
		$data['cambios'][2] = $cambio;


		$data['main_content'] = 'facturacion_electronica/form_mensaje_archivos_erroneos';
		$this->load->view('includes/template', $data);

	}

	function descargar_archivos_defectuosos(){
		$data['ubicacion'] = $this->input->post('ubicacion');
		$data['cantidad'] = $this->input->post('cantidad');
	   	$path_file = "upload/documentos_fallidos_".$nombre_pull.".zip";
		$data = file_get_contents($path_file); // Read the file's contents
		$name = 'documentos_fallidos.zip';

		force_download($name, $data);
	}   

	function borrar_carpeta($archivo,$carpeta){
		//break;
		unlink($archivo);
		$this->borrar_archivos_directorio($carpeta);
		rmdir($carpeta);
	}
	

	function borrar_archivos_directorio($directorio_origen){
		$directorio = opendir($directorio_origen);
		$filas = 1;
		//echo 'Lista de facturas <br><br><br>';
		while ($archivo = readdir($directorio)) //obtenemos un archivo y luego otro sucesivamente
		{
		    if (!is_dir($archivo))//verificamos si es o no un directorio
		    {
		    	unlink($directorio_origen.'/'.$archivo);
		    }
		    
		}

	}

	function buscar_nota_credito($comprobante,$documento,$notas_credito){
		//echo '<br>llego: '.$comprobante;
		if ($notas_credito!=null) {
			$lista_notas_credito = null;
			foreach ($notas_credito as $key => $value) {
//				var_dump($value['doc_modificado']);
				if ($comprobante == $value['doc_modificado'] && $documento == $value['identificacionComprador'] ) {
					//echo '<br>Encontre';
					$lista_notas_credito[$key] = $value;
				}
			}
			return $lista_notas_credito;
		}else{
			return null;
		}
	}

	function lector($archivo, $notas_credito){
		
		error_reporting(0);
		$validar = false;
		try {

			$xml = simplexml_load_file($archivo);
			
			if ($xml!=null) {
				$titulo = $xml->children();
				$comprobante = new SimpleXMLElement($titulo->comprobante);
				$validar = true;
			}else{
				if (!copy($archivo, 'facturas_erroneas/erronea'.date('Y-m-d H-m-s').'.xml')) {
				};
				return null;
			}
		} catch (Exception $e) {
			if (!copy($archivo, 'facturas_erroneas/erronea_'.date('Y-m-d H-m-s').'.xml')) {
			};
			return null;
		}
		
		$infoTributaria = $comprobante->infoTributaria;
		$seguro_campecino = 0.00;
		$infoAdicional = $comprobante->infoAdicional;
		if ($infoAdicional!=null) {
			foreach ($infoAdicional->campoAdicional as $value) {
				if ($value['nombre']=='segurocampesino') {
					$seguro_campecino = $value;
				}
			}
		}


		if ( $infoTributaria->codDoc== '01') {
			$infoFactura = $comprobante->infoFactura;
			$totalConImpuestos = $infoFactura->totalConImpuestos;
			
			$subtotal_0 = 0.00;
			$subtotal_12 = 0.00;
			$subtotal_14 = 0.00;
			$subtotal_15 = 0.00;
			
			$subtotal_excento = 0.00;
			$subtotal_no_objeto = 0.00;
			$iva_14_12 = 0.00;
			$iva_15_12 = 0.00;
			$ice = 0.00;
			$impuesto_red_botellas= 0.00;
			$bi_ice = 0.00;
			$total = 0.00;
			foreach ($totalConImpuestos->totalImpuesto as $key => $value) {
				
				/* SUBTOTALES IVA */
				//Iva 0%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '0' ) {
					$subtotal_0 += $value->baseImponible.'';
				}

				//Iva 12%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '2') {
					$subtotal_12 +=$value->baseImponible.'';
					$iva_14_12 += $value->valor.'';
				}

				//Iva 14%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '3') {
					$subtotal_14 += $value->baseImponible.'';
					$iva_14_12 += $value->valor.'';
				}

				//Iva 15%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '4') {
					$subtotal_15 += $value->baseImponible.'';
					$iva_15_12 += $value->valor.'';
				}

				//Iva No objeto
				if ($value->codigo == '2' && $value->codigoPorcentaje == '6') {
					$subtotal_no_objeto += $value->baseImponible.'';
				}

				//Iva excento
				if ($value->codigo == '2' && $value->codigoPorcentaje == '7') {
					$subtotal_excento += $value->baseImponible.'';
				}

				/* FIN SUBTOTALES IVA */

				if ($value->codigo == '3') {
					$bi_ice += $value->baseImponible.'';
					$ice += $value->valor.'';
				}

				/* */
				if ($value->codigo == '5') {
					$impuesto_red_botellas += $value->valor.'';
				}
			}

			$informe_factura=null;
			$informe_factura['fecha']= $infoFactura->fechaEmision;
			$informe_factura['vendedor']= $infoTributaria->razonSocial;
			$informe_factura['ruc']= $infoTributaria->ruc;
			$informe_factura['secuencial']= $infoTributaria->estab.'-'.$infoTributaria->ptoEmi.'-'.$infoTributaria->secuencial;

			$lista_notas_credito = $this->buscar_nota_credito($informe_factura['secuencial'],$informe_factura['identificacionComprador'],$notas_credito);
			$nc_subtotal_0 = 0.00;
			$nc_subtotal_14 = 0.00;
			$nc_subtotal_15 = 0.00;
			$nc_subtotal_12 = 0.00;
			$nc_subtotal_excento = 0.00;
			$nc_subtotal_no_objeto = 0.00;
			$nc_iva_14_12 = 0.00;
			$nc_iva_15_12 = 0.00;
			$nc_ice = 0.00;
			$nc_impuesto_red_botellas=0.00;
			$nc_bi_ice =0.00;
			$nc_total = 0.00;
			$existe_nc='NO';
			if ($lista_notas_credito !=null) {
				
				foreach ($lista_notas_credito as $key => $value) {
					$nc_subtotal_0 += $value['subtotal_0']+0.00;
					$nc_subtotal_12 += $value['subtotal_12']+0.00;
					$nc_subtotal_14 += $value['subtotal_14']+0.00;
					$nc_subtotal_15 += $value['subtotal_14']+0.00;
					$nc_subtotal_excento += $value['subtotal_excento']+0.00;
					$nc_subtotal_no_objeto += $value['subtotal_no_objeto']+0.00;
					/*$nc_iva_14_12 += $value['iva_12'];
					$nc_ice += $value['valor_ice'];
					$nc_impuesto_red_botellas  += $value['valor_irbpnr'];
					$nc_bi_ice += $value['subtotal_ice'];*/
					$nc_total += ($value['total'])+0.00;
					$existe_nc = 'SI';
				}
			}
			//echo '<br>Total: '.$nc_total;
			$informe_factura['autorizacion']= $xml->numeroAutorizacion;
			$informe_factura['comprador']= $infoFactura->razonSocialComprador;
			$informe_factura['subtotal_0']=  $subtotal_0 - $nc_subtotal_0;
			$informe_factura['subtotal_12']= $subtotal_12 - $nc_subtotal_12 ;
			$informe_factura['subtotal_14']= $subtotal_14 - $nc_subtotal_14  ;
			$informe_factura['subtotal_15']= $subtotal_15 - $nc_subtotal_15  ;
			$informe_factura['subtotal_excento']= $subtotal_excento - $nc_subtotal_excento;
			$informe_factura['subtotal_no_objeto']= $subtotal_no_objeto - $nc_subtotal_no_objeto;
			$informe_factura['subtotal_ice']= $bi_ice - $nc_bi_ice ;
			$informe_factura['valor_ice']= $ice - $nc_ice ;
			$informe_factura['iva_15']= $iva_15_12 - $nc_iva_15_12;
			$informe_factura['valor_irbpnr']= $ice - $nc_impuesto_red_botellas ;
			$informe_factura['propina']= ($infoFactura->propina) - $nc_propina;
			$informe_factura['seguro_campecino']= $seguro_campecino;
			$informe_factura['total']= ( (float) ($infoFactura->importeTotal) - ($nc_total));
			$informe_factura['existe_nc'] = $existe_nc;
			//echo '<br>Total: '.($infoFactura->importeTotal).' - NC Total: '.$nc_total.'  :  '.$informe_factura['total'];
			return $informe_factura;

		} else if ($infoTributaria->codDoc== '04'){
			return 'NC';
		} else{
			return null;;
		}
	}

	function leer_nota_credito($archivo){
		error_reporting(0);
		$validar = false;
		try {

			$xml = simplexml_load_file($archivo);
			
			if ($xml!=null) {
				$titulo = $xml->children();
				$comprobante = new SimpleXMLElement($titulo->comprobante);
				$validar = true;
			}else{
				if (!copy($archivo, 'facturas_erroneas/erronea'.date('Y-m-d H-m-s').'.xml')) {
			    
				};
				return null;
			}
		} catch (Exception $e) {
			if (!copy($archivo, 'facturas_erroneas/erronea_'.date('Y-m-d H-m-s').'.xml')) {
			};
			return null;
		}
		$infoTributaria = $comprobante->infoTributaria;
		//echo '<br> Nota credito'.$comprobante->infoNotaCredito;
		if ($infoTributaria->codDoc== '04') {
			$infoNotaCredito = $comprobante->infoNotaCredito;
			$totalConImpuestos = $infoNotaCredito->totalConImpuestos;
			
			$subtotal_0 = 0.00;
			$subtotal_12 = 0.00;
			$subtotal_14 = 0.00;
			$subtotal_15 = 0.00;
			$subtotal_excento = 0.00;
			$subtotal_no_objeto = 0.00;
			$iva_14_12 = 0.00;
			$iva_15_12 = 0.00;
			$ice = 0.00;
			$impuesto_red_botellas=0.00;
			$bi_ice = 0.00;
			$total = 0.00;
			foreach ($totalConImpuestos->totalImpuesto as $key => $value) {
				
				/* SUBTOTALES IVA */
				//Iva 0%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '0' ) {
					$subtotal_0 += $value->baseImponible.'';
					
				}

				//Iva 12%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '2') {
					$subtotal_12 +=$value->baseImponible.'';
					$iva_15_12 += $value->valor.'';
					
				}

				//Iva 14%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '3') {
					$subtotal_14 += $value->baseImponible.'';
					$iva_15_12 += $value->valor.'';
				}

				//Iva 15%
				if ($value->codigo == '2' && $value->codigoPorcentaje == '4') {
					$subtotal_15 += $value->baseImponible.'';
					$iva_15_12 += $value->valor.'';
				}

				//Iva No objeto
				if ($value->codigo == '2' && $value->codigoPorcentaje == '6') {
					$subtotal_no_objeto += $value->baseImponible.'';
				}

				//Iva excento
				if ($value->codigo == '2' && $value->codigoPorcentaje == '7') {
					$subtotal_excento += $value->baseImponible.'';
					
				}

				/* FIN SUBTOTALES IVA */

				if ($value->codigo == '3') {
					$bi_ice += $value->baseImponible.'';
					$ice += $value->valor.'';
				}

				/* */
				if ($value->codigo == '5') {
					$impuesto_red_botellas += $value->valor.'';
				}
			}

			
			$informe_nota_credito=null;
			$informe_nota_credito['fecha']= $infoFactura->fechaEmision;
			$informe_nota_credito['ruc']= $infoTributaria->ruc;
			$informe_nota_credito['secuencial']= $infoTributaria->estab.'-'.$infoTributaria->ptoEmi.'-'.$infoTributaria->secuencial;
			$informe_nota_credito['autorizacion']= $xml->numeroAutorizacion;
			$informe_nota_credito['comprador']= $infoFactura->razonSocialComprador;
			$informe_nota_credito['cliente']= $infoFactura->razonSocial;

			/*Datos de nota de credito */
			$informe_nota_credito['doc_modificado']= $infoNotaCredito->numDocModificado;

			$informe_nota_credito['subtotal_0']=  $subtotal_0 ;
			$informe_nota_credito['subtotal_12']= $subtotal_12 ;
			$informe_nota_credito['subtotal_14']= $subtotal_14 ;
			$informe_nota_credito['subtotal_15']= $subtotal_15 ;
			$informe_nota_credito['subtotal_excento']= $subtotal_excento ;
			$informe_nota_credito['subtotal_no_objeto']= $subtotal_no_objeto ;
			$informe_nota_credito['subtotal_ice']= $bi_ice ;
			$informe_nota_credito['valor_ice']= $ice ;
			$informe_nota_credito['iva_12']= $iva_14_12;
			$informe_nota_credito['iva_15']= $iva_15_12;
			$informe_nota_credito['valor_irbpnr']= $ice ;
			$informe_nota_credito['propina']= $infoNotaCredito->propina;
			$informe_nota_credito['total']= $subtotal_0+$subtotal_12+$subtotal_14+$subtotal_excento+$subtotal_no_objeto;
			return $informe_nota_credito;
		}else{
			return null;
		}
	}


	function generar_excel($Informe,$guardar,$codigo_al=null) {

		$objPHPExcel = new PHPExcel();
        // Establecer propiedades
		$objPHPExcel->getProperties()
		->setCreator("MyM Consultores")
		->setLastModifiedBy("MyM Consultores")
		->setTitle("Informe de facturas")
		->setSubject("Informe de facturas")
		->setDescription("Informe de facturas")
		->setKeywords("Excel Office 2007 openxml php")
		->setCategory("Informe de facturas");

		// Agregar Informacion
		$row=1;

		$objPHPExcel->setActiveSheetIndex(0)
		->setCellValue('A'.$row, 'FECHA')
		->setCellValue('B'.$row, 'RUC')
		->setCellValue('C'.$row, 'NUMERO DE FACTURA')
		->setCellValue('D'.$row, 'NUMERO DE AUTORIZACION')
		->setCellValue('E'.$row, 'VENDEDOR')
		->setCellValue('F'.$row, 'CLIENTE')
		->setCellValue('G'.$row, 'SUBTOTAL 0%')
		->setCellValue('H'.$row, 'SUBTOTAL 12%')
		->setCellValue('I'.$row, 'SUBTOTAL 14%')
		->setCellValue('J'.$row, 'SUBTOTAL 15%')
		->setCellValue('K'.$row, 'SUBTOTAL NO OBJETO')
		->setCellValue('L'.$row, 'SUBTOTAL EXCENTO')
		->setCellValue('M'.$row, 'SUBTOTAL ICE')
		->setCellValue('N'.$row, 'IVA 15% - 12%')
		->setCellValue('O'.$row, 'ICE')
		->setCellValue('P'.$row, 'IRBPNR')
		->setCellValue('Q'.$row, 'Propina')
		->setCellValue('R'.$row, 'Seguro Campesino')
		->setCellValue('S'.$row, 'TOTAL')
		->setCellValue('T'.$row, 'Nota de credito');
		$row=2;
		//echo '<pre>'.var_export($Informe, true).'</pre>';exit;
		foreach ($Informe as $valor) {
			
			 if ($valor=='NC') {
				
			}else if ($valor!=null) {
				$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$row, (String) $valor['fecha'])
				->setCellValueExplicit('B'.$row, (String) ''.$valor['ruc'],PHPExcel_Cell_DataType::TYPE_STRING)
				->setCellValue('C'.$row, (String) ''.$valor['secuencial'])
				->setCellValueExplicit('D'.$row, (String)$valor['autorizacion'],PHPExcel_Cell_DataType::TYPE_STRING)
				->setCellValue('E'.$row, (String) $valor['vendedor'])
				->setCellValue('F'.$row, (String) $valor['comprador'])
				->setCellValue('G'.$row, (String) $valor['subtotal_0'])
				->setCellValue('H'.$row, $valor['subtotal_12'])
				->setCellValue('I'.$row, $valor['subtotal_14'])
				->setCellValue('J'.$row, $valor['subtotal_15'])
				->setCellValue('K'.$row, $valor['subtotal_no_objeto'])
				->setCellValue('L'.$row, $valor['subtotal_excento'])
				->setCellValue('M'.$row, $valor['subtotal_ice'])
				->setCellValue('N'.$row, $valor['iva_15'])
				->setCellValue('O'.$row, $valor['valor_ice'])
				->setCellValue('P'.$row, $valor['valor_irbpnr'])
				->setCellValue('Q'.$row, $valor['propina'])
				->setCellValue('R'.$row, $valor['seguro_campecino'])
				->setCellValue('S'.$row, $valor['total'])
				->setCellValue('T'.$row, $valor['existe_nc']);
				$row++;
			}else{
				$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A'.$row.':E'.$row);
				$objPHPExcel->getActiveSheet()->getStyle('A'.$row.':E'.$row)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
				$styleArray = array(
			    'font'  => array(
			        'bold'  => true,
			        'color' => array('rgb' => 'F28A8C'),
			        'name'  => 'Verdana'
			    ));
				$objPHPExcel->getActiveSheet()->getStyle('A'.$row.':E'.$row)->applyFromArray($styleArray);

				$objPHPExcel->setActiveSheetIndex(0)
				->setCellValue('A'.$row, 'Factura con Formato Ilegible');
				$row++;
			}
			
		}


		// Renombrar Hoja

		$objPHPExcel->getActiveSheet()->setTitle('Facturas');


		// Establecer la hoja activa, para que cuando se abra el documento se muestre primero.

		$sheet = $objPHPExcel->getActiveSheet();
		$cellIterator = $sheet->getRowIterator()->current()->getCellIterator();
		$cellIterator->setIterateOnlyExistingCells( true );
		/** @var PHPExcel_Cell $cell */
		foreach( $cellIterator as $cell ) {
			$sheet->getColumnDimension( $cell->getColumn() )->setAutoSize( true );
		}


		if (!$guardar) {
		    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment;filename="Informe Facturacion.xlsx"');
			header('Cache-Control: max-age=0');

			$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
			$objWriter->save('php://output');

	        return null;
    	}else{
    		
    		$file_excel = "upload/informe_facturacion_".$codigo_al.".xlsx";
			$objWriter = new PHPExcel_Writer_Excel2007($objPHPExcel);
			$objWriter->save(str_replace('.php', '.xlsx', $file_excel));
			return $file_excel;
		}
	}

	function enviar_email_alerta() {
		//Descargar la libreria
		$this->load->library('email'); 
		$config['mailtype'] =  'html';
		$this->email->initialize($config);	
		//Capturo los imput
		$nombre = $this->input->post('nombre'); // capturo los campos del formulario
		$telefono = $this->input->post('telefono'); // capturo los campos del formulario
		$email = $this->input->post('email'); // capturo los campos del formulario
		$email = $this->input->post('asunto'); // capturo los campos del formulario
		$mensaje = $this->input->post('mensaje'); // capturo los campos del formulario
		$this->email->from('informacion@mymfacturador.com', 'Mym Facturador' ); //email desde donde envio
		$this->email->to('santyxlr8@live.com' ); // email a donde envio
		//$this->email->cc('pabloorejuela@hotmail.com' ); // email a donde envio
		$this->email->subject('Recuerde vaciar los archivos en el servidor'); 
		$this->email->message('<p>Estimado administrador la carpeta de archivos conservados en M&M Facturador<br>ha alcanzado los 50MB El sistema borrara los archivos contenidos en dicho directorio</p><br><br><br>Saludos cordiales'); 
		$this->email->send();
	}

}

/* End of file facturacion_electronica.php */
/* Location: ./application/controllers/facturacion_electronica.php */