
<?php echo form_open('facturacion_electronica/descargar_archivos_defectuosos',' '); ?>
<input type="hidden" name="ubicacion" id="ubicacion" value="<?php echo $ubicacion; ?>">
<input type="hidden" name="cantidad" id="cantidad" value="<?php echo $cantidad; ?>">

<div class="row">
	<div class="col-md-10">
		<div class="jumbotron">
			<h1>Facturación Electrónica </h1>
			<h3 style="color: red"><i class="fa fa-exclamation-triangle" aria-hidden="true"></i><strong>Hemos detectado algunos archivos dañados o con formato incorrecto.</strong></h3>
			<p>
			El informe fue generado, pero se detectaron archivos xml con formato incorrecto o archivos dañados, los cuales no aparecerán en su informe.
			Use los botones a continuación para descargar los documentos y archivos correspondientes.
			</p>
			
			<p style="padding: 10px;">
				<a class="btn btn-danger btn-lg"  role="button" href="<?php echo base_url().''.$ubicacion; ?>">
				<i class="fa fa-file-archive-o" aria-hidden="true"></i>Descargar Archivos con error</a>
				<a class="btn btn-success btn-lg" role="button" href="<?php echo base_url().''.$ruta_excel; ?>">
				<i class="fa fa-table" aria-hidden="true"></i>Descargar Informe</a>
				<a class="btn btn-primary btn-lg"  role="button" href="<?php echo base_url(); ?>">
				<i class="fa fa-home" aria-hidden="true"></i>Ir al Inicio</a>
			</p>
			<div class="col-md-4">
				<div><?php echo 'Versión de la app: ' . $this->config->item('version'); ?> </div>
				<div><span><?php echo 'Versión del framework: ' . CI_VERSION; ?></span></div>
			</div> 
		</div>
	</div>
	
</div>

<?php echo form_close(); ?>

