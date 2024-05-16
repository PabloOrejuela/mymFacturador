<?php echo form_open_multipart('facturacion_electronica/subida_multiple',' '); ?>
<div class="row">
	<div class="col-md-10">
		<div class="jumbotron">
			<h1>Facturación Electrónica </h1>
			<h3><strong>Generar informe excel</strong></h3>
			<p>Para generar el informe de facturación, comprima en formato <strong>.zip</strong> las facturas electrónicas. Una vez haya terminado la compresion seleccione la ubicación de su archivo .zip usando el botón seleccionar.<br><br>Haga clic sobre el botón <strong>Generar</strong> para iniciar el proceso</p>
			<input name="userfile[]" id="archivo_productos_excel" type="file"  required="required"  class="form-control" accept=".zip" />
			<p style="padding: 10px;">
				<button type="submit" class="btn btn-primary btn-lg"  role="button">
				<span class="glyphicon glyphicon-download-alt" id="download-icon"></span>Generar</button>
				<button type="reset" class="btn btn-default btn-lg" id="reestablecer_btn">Reestablecer</button>
			</p>
			<div class="col-md-4">
				<div><?php echo 'Versión de la app: ' . $this->config->item('version'); ?> </div>
				<div><span><?php echo 'Versión del framework: ' . CI_VERSION; ?></span></div>
			</div>
		</div>
	</div>
	
</div>

<?php echo form_close(); ?>

