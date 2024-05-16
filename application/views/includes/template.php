<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests"> -->
    <meta name="description" content="">
    <meta name="author" content="Santiago Viteri">
    <link rel="icon" href="<?php  echo base_url(); ?>images/invoice.png">
    <title>MyM facturador</title>

    <!-- Bootstrap Core CSS -->
    <link href="<?php  echo base_url(); ?>css/bootstrap.min.css" rel="stylesheet">
    <link href="<?php  echo base_url(); ?>css/jquery.toast.min.css" rel="stylesheet" type="text/css"/>
    <link href="<?php  echo base_url(); ?>css/jquery-ui.min.css" rel="stylesheet" type="text/css"/>
    <!-- Custom CSS -->
    <link href="<?php echo base_url(); ?>css/sb-admin.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>css/estilos.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>css/bootstrap_datepicker.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>css/tablas/bootstrap_datatable.css" rel="stylesheet">
    <link href="<?php echo base_url(); ?>css/estilos.css" rel="stylesheet">
     

    <!-- Custom Fonts -->
    <link href="<?php  echo base_url(); ?>font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">
    <link href="<?php  echo base_url(); ?>css/pedidos.css" rel="stylesheet" type="text/css">
    <script src="<?php echo base_url(); ?>js/jquery.js"></script>
    <script src="<?php echo base_url(); ?>js/jquery.toast.min.js" type="text/javascript"></script>
    <script src="<?php echo base_url(); ?>js/formularios_dinamicos.js"></script>
    <script src="<?php echo base_url(); ?>js/moment-with-locales.js"></script>
    <script src="<?php echo base_url(); ?>js/bootstrap_datepicker.js"></script>

    <script src="<?php echo base_url(); ?>js/adicionales/boostrapt_paginacion.js"></script>
    <script src="<?php echo base_url(); ?>js/adicionales/boostrap_min_pagination.js"></script>
    
    <?php header('Access-Control-Allow-Origin: *'); ?>

    
    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
        <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
        <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->
    <script>

    $(document).ready(function() {
        $('.tabla_datos').DataTable();
    });
            $(function (){
              /*muestra un mensaje toast desde el controlador*/
              <?php if(isset($toast)&& $toast!=null){?>
              $.toast({
                    heading: '<?php echo $toast['titulo'];?>',
                    text: '<?php echo $toast['mensaje'];?>',
                    showHideTransition: 'slide',
                    icon: '<?php echo $toast['icono'];?>'
                });
              <?php } ?>
            });

            function crear_toast(titulo,cuerpo,icono='info'){
               $.toast({
                    heading: titulo,
                    text: cuerpo,
                    showHideTransition: 'slide',
                    icon: icono
                });
            }
        </script>
</head>

<body style="background-color: white;">

    <!-- Navigation -->
    
     <nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
                    <span class="sr-only">Toggle navigation</span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                    <span class="icon-bar"></span>
                </button>
                <a class="navbar-brand" href="<?php echo base_url(); ?>">MyM Facturación</a>
            </div>
            <!-- Top Menu Items -->
            
            <ul class="nav navbar-right top-nav">
                <li class="dropdown" >

                    <a href="#" class="dropdown-toggle" id="link_ver_cambios" data-toggle="dropdown">
                        <button 
                            id="link_cambios" 
                            data-container="body" 
                            title="Actualizacion" 
                            data-toggle="popover" 
                            data-placement="bottom" 
                            data-content="Se agregaron nuevas caracteristicas " 
                            style="background-color: transparent;height: 0px;width: 0px;border: none">
                        </button><i class="fa fa-envelope"></i><span class="badge"></span><b class="caret"></b>
                    </a>
                     <ul class="dropdown-menu message-dropdown">
                     <?php 
                        if($cambios!=null){
                            $cambiosOrdenado = array_reverse($cambios);
                            foreach ($cambiosOrdenado as  $value) { ?>
                                <li class="message-preview">
                                <a href="#"  onclick="mostrar_informacion('<?php echo $value['descripcion']; ?>')">
                                    <div class="media">
                                            <span class="pull-left">
                                                <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                                            </span>
                                            <div class="media-body">
                                                <h5 class="media-heading"><strong><?php echo $value['titulo'] ?></strong>
                                                </h5>
                                                <p class="small text-muted"><i class="fa fa-clock-o"></i> <?php echo $value['fecha']; ?></p>
                                                <p>
                                                <?php echo substr($value['descripcion'],0,40); ?>... 
                                                </p>
                                                
                                                <input id="descripcion_cambio" type="hidden" value="<?php echo $value['descripcion']; ?>" >
                                            </div>
                                        </div>
                                        </a>
                                </li>
                          <?php }}?>
                     </ul>
                    
                </li>
                
               
            </ul>
        </nav>
    <!-- Page Content -->
    <div id="page-wrapper">
    <div class="container">
    <!-- Call to Action Section -->
            
        <div class="row" style="margin-top: 5px;">
            <?php echo $this->load->view($main_content); ?>
        </div>
            
        <hr>
        <!-- Footer -->
        <footer style="background-color: white;">
            <div class="row">
                <div class="col-lg-12">
                    <p>Copyright &copy; Appdvp Desarrollo</p>
                </div>
            </div>
        </footer>
        <!-- Modal -->
        <div class="modal fade" id="modal_detalle" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Detalles de Actualización</h4>
            </div>
            <div class="modal-body">
                <p id="texto_detalle"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal"><span class="glyphicon glyphicon-ok"></span> OK Entiendo !!!</button>
            </div>
            </div>
        </div>
        </div>
    </div>
    </div>
    <!-- /.container -->

    <!-- jQuery -->
    

    <!-- Bootstrap Core JavaScript -->
    <script src="<?php echo base_url(); ?>js/bootstrap.min.js"></script>

    <!-- Script to Activate the Carousel -->
    <script>
    $('.carousel').carousel({
        interval: 5000 //changes the speed
    })
    </script>
    <script type="text/javascript">
        $(function () {
            $('#datetimepicker').datetimepicker();
            $('#datetimepicker_1').datetimepicker();
            $('#link_cambios').popover('show');
            $('#link_ver_cambios').on('mouseover', function(event) {
                $('#link_cambios').popover('hide');
            });
        });

        function mostrar_informacion(texto) {
            $('#modal_detalle').modal('show');
            $('#texto_detalle').text(texto);
        }
    </script>

</body>

</html>
