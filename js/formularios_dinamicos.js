var base_url='http://localhost/sea_pedidos/';
 
$(function () {
	
	$('#btn_buscar_productos_pedido').on('click', function(event) {
		var imagen = '<tr id="tr_cargando" style="display: none;">\n\
        			<td colspan="3">\n\
        				<img src="<?php base_url(); ?>images/loader/loader_1.gif" alt="Cargando....">\n\
        			</td>\n\
        		</tr>';
		$('#tbody_productos').empty();
		$('#tbody_productos').append(imagen);

		var criterio = $('#txt_criterio_producto').val();
		$.ajax({
			url: base_url+'pedidos/get_productos_ajax',
			type: 'POST',
			data: {criterio_busqueda: ''+criterio},
		})
		.done(function(productos) {
			var json_arr = $.parseJSON(productos);
			var contenido = '';
			if (json_arr.length > 0) {
				for (var i = 0; i < json_arr.length; i++) {
					contenido += '<tr><td colspan="3"> '+
					'<div class="row">'+
					  '<div class="col-xs-6 col-sm-6 col-lg-6 col-md-6">'+
					    '<a href="#" class="thumbnail">'+
					    '  <img src="'		+json_arr[i].imagen_producto+'" width="55px" height="25px" alt="Productos">'+
					    '</a>'+
					  '</div>'+
					  ' <strong>Codigo :</strong>'		+json_arr[i].codigo_producto+' <br>'+
					  ' <strong>Producto :</strong> '	+json_arr[i].nombre_producto+' <br>'+
					  ' <strong>Precio :</strong> $'	+json_arr[i].precio_producto+' <br>'+
					   ' <p>'	+json_arr[i].descripcion_producto+' </p>'+
					   ' <button class="btn btn-primary" data-dismiss="modal" '+
					   'onclick="escojer_producto('+json_arr[i].id_producto+',\''+json_arr[i].codigo_producto+'\',\''+json_arr[i].nombre_producto+'\',\''+json_arr[i].precio_producto+'\');">'+
					   '<span class="glyphicon glyphicon-ok" '+
					   '></span>Escojer</button>'+
					'</div>'+
					' </td></tr>';
				}
			}else{

			}
			$('#tbody_productos').empty();
			$('#tbody_productos').append(contenido);

			
		})
		.fail(function() {
			console.log("No se pudo completar la peticion");
		})
		.always(function() {
			
		});
		
	});

	$('.calculo_pedido').on('keyup',  function(event) {
		calcular_detalle_pedido();
	});

	$('.calculo_pedido').on('change',  function(event) {
		calcular_detalle_pedido();
	});

	$('.calculo_pedido').on('blur',  function(event) {
		var id_producto = $('#id_producto').val();
			if (id_producto!=null && id_producto!='') {
			calcular_detalle_pedido();
		}else{
			crear_toast('Mensaje','Escoja un producto para el pedido')
		}
	});

	$('#span_met').on('mouseover', function(event) {
		$('#span_met').tooltip('show');
	});
	$('#span_mmr').on('mouseover', function(event) {
		$('#span_mmr').tooltip('show');
	});
	

	$('.filtrado').on('change', function(event) {
		buscar_pedidos_por_distribuidor();
	});

	$('.filtrado_pedidos_all').on('change', function(event) {
		buscar_pedidos_todos();
	});

	$('#archivo_adjunto_pedido').on('change', function(event) {
		calcular_detalle_pedido();
	});

	$('#btn_registra_pedido').on('click', function(event) {
		crear_toast('Procesando Pedido','Su pedido se esta procesando por favor espere !!! ');
	});

	$('#buscar_cliente_criterio').on('click', function(event) {
		buscar_clientes_by_criterio();
	});

	$('#valor_abono').on('keyup',  function(event) {
		verificar_abono_con_saldo();
	});
	
	$('input:radio[name=radio_opciones]').on('change', function(event) {
		calcular_detalle_pedido();
	});



});

function consultar_detalle_pedido(id_pedido) {
	
	$.ajax({
		url: base_url+'administracion/consultar_pedido_por_id_ajax',
		type: 'POST',
		data: {pedido_id: ''+id_pedido},
	})
	.done(function(respuesta) {
		var informacion = respuesta.split('<separador>');
		var j_pedido = informacion[0];
		var j_detalle = informacion[1];
		var j_pago = informacion[2];
		contenido_pedido ='';
		var json_pedido = $.parseJSON(j_pedido);
		var json_detalle = $.parseJSON(j_detalle);
		if (json_pedido != null) {
				
			contenido_pedido += '<tr><td colspan="4" class="div_centro"> <strong>Pedido</strong></td></tr>\n\
			<tr>\n\
              <td>Codigo Pedido:</td>\n\
              <td>'+json_pedido.codigo_pedido+'</td>\n\
              <td>Fecha:</td>\n\
              <td >'+json_pedido.fecha_pedido+'</td>\n\
            </tr>\n\
            <tr><td colspan="4" class="div_centro"> <strong>Datos de Cliente</strong></td></tr>\n\
            <tr>\n\
              <td>Cédula:</td>\n\
              <td>'+json_pedido.documento_cliente+'</td>\n\
              <td>Cliente :</td>\n\
              <td>'+json_pedido.nombre_cliente+'</td>\n\
            </tr>\n\
            <tr><td colspan="4" class="div_centro"> <strong>Producto</strong></td></tr>\n\
            <tr><td><strong>Código</strong></td><td colspan="2"><strong>Descripción</strong></td><td><strong>Medida</strong></td></tr>\n\
            <tr>\n\
            <td>'+json_detalle[0].codigo_producto+'</td>\n\
            <td colspan="2">'+json_pedido.nombre_producto+'</td>\n\
            <td class="td_numero">'+json_detalle[0].med_facturacion+' Metros Cuadrados</td>\n\
            </tr>\n\
            <tr>\n\
              <td colspan="2" class="div_nuevo">Ancho:</td>\n\
              <td colspan="2" class="td_numero">'+json_detalle[0].med_tol_ancho+' Metros Cuadrados</td>\n\
            </tr>\n\
            <tr>\n\
              <td class="div_nuevo" colspan="2">Alto :</td>\n\
              <td colspan="2" class="td_numero">'+json_detalle[0].med_tol_alto+' Metros Cuadrados</td>\n\
            </tr>\n\
            ';
				
			}
		var json_pago = $.parseJSON(j_pago);
		var suma_pagos=0;
		if (json_pago != null && json_pago.length > 0) {
			contenido_pedido +='<tr><td colspan="4" style="text-align: center; background-color: #009AA5 ;color: white;">Detalle de Pagos</td></tr>';
			contenido_pedido +='<tr><td><strong>Fecha</strong></td><td><strong>Banco</strong></td><td><strong>Comprobante</strong></td><td><strong>Valor</strong></td></tr>';
				for (var i = 0; i < json_pago.length; i++) {
					contenido_pedido += '<tr>\n\
		              <td id="">'+json_pago[i].fecha_pago+'</td>\n\
		              <td >'+json_pago[i].banco_pago+' </td>\n\
		              <td >'+json_pago[i].comprobante_pago+' </td>\n\
		              <td >$ '+json_pago[i].valor_pago+' </td>\n\
		            </tr>\n\
		            ';
		            suma_pagos +=  parseFloat(json_pago[i].valor_pago);
				}
				
			}else{
				contenido_pedido +='<tr><td colspan="4" style="text-align: center;"><strong>Sin pagos registrados</strong></td></tr>';
			}
			var saldo = parseFloat(json_pedido.total_pedido)-parseFloat(suma_pagos);
			contenido_pedido +='<tr>\n\
              <td colspan="3"><strong>Saldo pendiente:</strong></td>\n\
              <td class="div_nuevo">$ '+parseFloat(saldo)+' </td>\n\
            </tr>';
            contenido_pedido +='<tr>\n\
              <td colspan="4"><img src="'+json_pedido.imagen_producto+'" alt="Sin imagen" style="height: 300px;width: 100%;"  /></td>\n\
            </tr>';


		console.log('Consulta: '+json_pedido.codigo_pedido);
		$('#tb_datos_pedido').empty();
		$('#tb_datos_pedido').append(contenido_pedido);

	})
	.fail(function() {
		console.log("error");
	})
	.always(function() {
		console.log("complete");
	});
	
}

function buscar_clientes_by_criterio() {
	var criterio_busqueda = $('#criterio_busqueda').val();
	var imagen = '<tr id="tr_cargando" style="display: none;">\n\
        			<td colspan="3">\n\
        				<img src="<?php base_url(); ?>images/loader/loader_1.gif" alt="Cargando....">\n\
        			</td>\n\
        		</tr>';
		$('#tb_clientes_table').empty();
		$('#tb_clientes_table').append(imagen);
	$.ajax({
		url: base_url+'pedidos/buscar_cliente_por_criterio_ajax',
		type: 'POST',
		data: {criterio: ''+criterio_busqueda},
	})
	.done(function(respuesta) {
		var json_arr = $.parseJSON(respuesta);
		var contador =0;
			var contenido = '';
			if (json_arr != null && json_arr.length > 0) {
				for (var i = 0; i < json_arr.length; i++) {
					contenido += '<tr>\n\
							<td>'+json_arr[i].documento_cliente+'</td>\n\
							<td>'+json_arr[i].nombre_cliente+'</td>\n\
							<td>'+json_arr[i].direccion_cliente+'</td>\n\
							<td>\n\
							<button class="btn btn-primary" data-dismiss="modal" onclick="seleccionar_pedido('+json_arr[i].id_cliente+',\''+json_arr[i].nombre_cliente+'\');">Seleccionar</button>\n\
							</td>\n\
						</tr>';
						contador++;
				}
			}

			if (contador==0) {
				contenido +='<tr><td colspan="4">Sin clientes registrados <a href="'+base_url+'distribuidores/gestionar_clientes">Registrar Clientes</a></td></tr> ';
			}
			$('#tb_clientes_table').empty();
			$('#tb_clientes_table').append(contenido);
	})
	.fail(function() {
		console.log("error");
	})
	.always(function() {
		console.log("complete");
	});
	
}

function seleccionar_pedido(id,nombre) {
	$('#id_cliente').val(id);
	$('#nombre_cliente').val(nombre);
	calcular_detalle_pedido();
}


function escojer_producto(id,codigo,nombre,precio) {
	
	$('#id_producto').val(id);
	$('#txt_codigo_producto').val(codigo);
	$('#txt_nombre_producto').val(nombre);
	$('#txt_precio_producto').val(precio);
	calcular_detalle_pedido();
}

function calcular_detalle_pedido() {
	var met_ancho = parseFloat($('#met_ancho').val());
	var met_alto =  parseFloat( $('#met_alto').val());
	var precio_producto = parseFloat($('#txt_precio_producto').val());
	var met_total = met_ancho*met_alto;
	$('#met_total').val(met_total);
	var med_rendondeada = Math.ceil(met_total);
	
	var medida=0;
	var entero=0;
	var decimal=0;
	var medida_factura=0;

	if (met_total+''.indexOf('.')>-1) {
		 medida  = (met_total+"").split('.');
		 entero  =medida[0];
		 decimal = medida[1];
		 medida_factura = 0;
	}else if (met_total+''.indexOf(',')>-1) {
		 medida  = (met_total+"").split(',');
		 entero  =medida[0];
		 decimal = medida[1];
		 medida_factura = 0;
	}else{
		 entero  = mmr_tor_redondeado;
		 decimal = 0;
		 medida_factura = 0;
	}

	
	var medida_facturacion=0;
	if ('0.'+decimal >= 0.05) {
		    med_rendondeada = med_rendondeada;
		    console.log('Superior');
		}else{
			med_rendondeada = Math.floor(met_total);
			console.log('Inferior');
		}
	var id_producto = $('#id_producto').val();
	// 
	$('#met_tor_redondeado').val(med_rendondeada);

	var lado_promedio_red = Math.sqrt(med_rendondeada);
	var perimetro_red = lado_promedio_red*4;

	var lado_promedio_real = Math.sqrt(met_total);
	var perimetro_real = lado_promedio_real*4;

	var diferencia_redondeda = perimetro_red-perimetro_real;
	var diferencia_por_lado = (diferencia_redondeda/4);

	/* Optencion de datos preferencias */
	var opcion = $('input:radio[name=radio_opciones]:checked').val();

	/* Suma de exceso */
	var mmr_ancho = 0;
	var mmr_alto = 0;
	var dif_ancho=0;
	var dif_alto=0;
	if (opcion=='0') {
		 mmr_ancho = met_ancho+diferencia_por_lado;
		 mmr_alto = met_alto+diferencia_por_lado;

		 dif_ancho = met_ancho-mmr_ancho;
		 dif_alto = met_alto-mmr_alto;

	}else if (opcion == '1') {
		 mmr_ancho = met_ancho;
		 mmr_alto = met_alto+(diferencia_por_lado*2);

		 dif_ancho = 0;
		 dif_alto = met_alto-mmr_alto;

	}else if (opcion == '2') {
		 mmr_ancho =  met_ancho+(diferencia_por_lado*2);
		 mmr_alto = met_alto;

		 dif_ancho = met_ancho-mmr_ancho;
		 dif_alto = 0;
	}

	/* Calculo de sobrante */
	

	$('#mmr_ancho').val(mmr_ancho);
	$('#mmr_alto').val(mmr_alto);

	$('#dif_ancho').val(dif_ancho);
	$('#dif_alto').val(dif_alto);


	var total_factura_pedido = precio_producto*med_rendondeada;
	$('#txt_total_factura_pedido').val(total_factura_pedido);

	$('#medida_facturacion').val(med_rendondeada);
	if (total_factura_pedido >0 && id_producto!=null && id_producto!='') {
		
		$('#btn_adjuntar_pago_pedido').removeAttr('disabled');
		 var archivo_adjunto = $('#archivo_adjunto_pedido').val();
		if ( archivo_adjunto!=null && archivo_adjunto!='') {
			$('#btn_registra_pedido').removeAttr('disabled');
			verificar_datos_pedido();
		}else{
			$('#btn_registra_pedido').attr('disabled', 'disabled');
		}
		}else{
			
			$('#btn_adjuntar_pago_pedido').attr('disabled', 'disabled');
		}

}

function verificar_datos_pedido() {
	var id_cliente = $('#id_cliente').val();
	if (id_cliente!=null && id_cliente!='') {
		$('#btn_registra_pedido').removeAttr('disabled');
	}else{
		$('#btn_registra_pedido').attr('disabled', 'disabled');
	}
}

function buscar_pedidos_por_distribuidor() {
	var mes = $('#id_mes').val();
	var estado = $('#id_estado').val();
	var imagen = '<tr id="tr_cargando" style="display: none;">\n\
        			<td colspan="3">\n\
        				<img src="<?php base_url(); ?>images/loader/loader_1.gif" alt="Cargando....">\n\
        			</td>\n\
        		</tr>';
		$('#tbody_lista').empty();
		$('#tbody_lista').append(imagen);
	$.ajax({
		url: base_url+'pedidos/get_lista_pedido_criterio_ajax',
		type: 'POST',
		data: {id_estado: ''+estado,id_mes: ''+mes},
	})
	.done(function(pedidos) {
		var json_arr = $.parseJSON(pedidos);
			var contenido = '';
			if (json_arr != null && json_arr.length > 0) {
				for (var i = 0; i < json_arr.length; i++) {
					
							var color='';
							var avance='';
							if(json_arr[i].id_estado=='1'){
									color = 'danger';
									avance = '20';
								}else if(json_arr[i].id_estado=='2'){
									color = 'info';
									avance = '40';
								}else if(json_arr[i].id_estado=='3'){
									color = 'info';
									avance = '80';
								}else if(json_arr[i].id_estado=='4'){
									color = 'success';
									avance = '100';
								}
					contenido += '<tr>\n\
							<td> '+ (i+1) +' </td>\n\
							<td>'+json_arr[i].codigo_pedido+'</td>\n\
							<td>'+json_arr[i].fecha_pedido+'</td>\n\
							<td>'+json_arr[i].nombre_producto+'</td>\n\
							<td>'+json_arr[i].nombre_usuario+'</td>\n\
							<td>\n\
								<div class="progress">\n\
								  <div class="progress-bar progress-bar-'+color+'" role="progressbar" aria-valuenow="'+avance+'" aria-valuemin="0" aria-valuemax="100" style="width: '+avance+'%">\n\
								   '+avance+'%\n\
								  </div>\n\
								</div>\n\
								'+json_arr[i].nombre_estado+'\n\
							</td>\n\
							<td> \n\
							<button data-toggle="modal" data-target="#modal_detalles" class="btn btn-primary" onclick="consultar_detalle_pedido('+json_arr[i].id_pedido+');"> Ver detalles</button>\n\
							</td>\n\
						</tr>';
				}
			}
			$('#tbody_lista').empty();
			$('#tbody_lista').append(contenido);
	})
	.fail(function() {
		console.log("error");
	})
	.always(function() {
		console.log("complete");
	});
	
}

function buscar_pedidos_todos() {
	var mes = $('#id_mes').val();
	var estado = $('#id_estado').val();
	var imagen = '<tr id="tr_cargando" style="display: none;">\n\
        			<td colspan="3">\n\
        				<img src="<?php base_url(); ?>images/loader/loader_1.gif" alt="Cargando....">\n\
        			</td>\n\
        		</tr>';
		$('#tbody_lista').empty();
		$('#tbody_lista').append(imagen);
	$.ajax({
		url: base_url+'pedidos/get_lista_pedido_criterio_ajax',
		type: 'POST',
		data: {id_estado: ''+estado,id_mes: ''+mes},
	})
	.done(function(pedidos) {
		var json_arr = $.parseJSON(pedidos);
			var contenido = '';
			var texto='Atender';
			var icono='';
			var color='btn btn-default';
			if (json_arr != null && json_arr.length > 0) {
				for (var i = 0; i < json_arr.length; i++) {
					contenido += '<tr>\n\
							<td> '+ (i+1) +' </td>\n\
							<td>'+json_arr[i].codigo_pedido+'</td>\n\
							<td>'+json_arr[i].fecha_pedido+'</td>\n\
							<td>'+json_arr[i].nombre_producto+'</td>\n\
							<td>'+json_arr[i].nombre_usuario+'</td>\n\
							<td>'+json_arr[i].nombre_estado+'</td>';
							if (json_arr[i].id_estado==1) {
								texto = 'Importacion Realizada';
								icono ='<i class="fa fa-exchange" aria-hidden="true"></i>';
								color = 'btn btn-default';
							}else if(json_arr[i].id_estado==2){
								texto = 'Importacion Concluida';
								icono ='<i class="fa fa-check-square" aria-hidden="true"></i>';
								color = 'btn btn-primary';
							}else if(json_arr[i].id_estado==3){
								texto = 'Registrar Envío';
								icono ='<i class="fa fa-truck" aria-hidden="true"></i>';
								color = 'btn btn-success';
							}
							contenido +='<td>\n\
								<button class="'+color+'" \n\
								onclick="abrir_model_correspondiente('+json_arr[i].id_estado+','+json_arr[i].id_pedido+');">\n\
									'+icono+' '+texto+'\n\
								</button>\n\
							</td>\n\
						</tr>';
				}
			}else{
				contenido += '<tr><td colspan="7"></td></tr>';
			}
			$('#tbody_lista').empty();
			$('#tbody_lista').append(contenido);
	})
	.fail(function() {
		console.log("error");
	})
	.always(function() {
		console.log("complete");
	});
	
}

function abrir_model_correspondiente(id_estado,id_pedido) {
	if (id_estado == '1') {
		get_datos_pedido_by_id(id_pedido,'td_imagen_mural_importacion');
		$('#id_pedido_1').val(id_pedido);
		$('#modal_pendiente').modal('show');
	}else if (id_estado == '2') {
		get_datos_pedido_by_id(id_pedido,'td_imagen_mural');
		$('#id_pedido_2').val(id_pedido);
		$('#modal_importacion').modal('show');
	}else if (id_estado == '3') {
		get_datos_pedido_by_id(id_pedido,'td_mural_envio');
		$('#id_pedido_3').val(id_pedido);
		$('#modal_envio').modal('show');
	}
	


}

function get_datos_pedido_by_id(id,td_name) {
	$.ajax({
		url: base_url+'administracion/get_datos_pedido_ajax',
		type: 'POST',
		data: {id_pedido: ''+id},
	})
	.done(function(pedido) {
			var json_arr = $.parseJSON(pedido);
			var html_imagen  ='<label>Pedido: '+json_arr.codigo_pedido+'</label><br>';
			html_imagen += '<img src="'+json_arr.imagen_producto+'" alt="Imagen no disponible" height="350" width="300" id="img_mural">';
			html_imagen +='<label>Codigo: '+json_arr.codigo_producto+'</label><br>';
			html_imagen +='<label>Descripcion: '+json_arr.nombre_producto+'</label><br>';
			$('#'+td_name).empty();
			$('#'+td_name).append( html_imagen+'' );
		
	})
	.fail(function() {
		console.log("error");
	})
	.always(function() {
		console.log("complete");
	});
	
}

function realizar_abono_asignacion(id_pedido,saldo_pedido,codigo_pedido,total_factura) {
	$('#id_pedido').val(id_pedido);
	$('#label_saldo_info').text(saldo_pedido);
	$('#saldo_anterior').val(saldo_pedido);
	$('#total_factura').val(total_factura);
	$('#codigo_pedido').val(codigo_pedido);
	console.log('Saldo'+saldo_pedido);
}

function verificar_abono_con_saldo() {
	var abono = parseFloat($('#valor_abono').val());
	var saldo = parseFloat($('#label_saldo_info').text());
	console.log('abono: '+abono);
	if (abono >	 saldo ) {
		$('#label_advertencia_pago').text('Advertencia: El Abono realizado excede el saldo ');
	}else if ( (isNaN(abono)) || abono == 0 ) {
		$('#label_advertencia_pago').text('Ingrese un abono mayor a cero');
	}else {
		$('#label_advertencia_pago').text('Pago correcto');
	}

}