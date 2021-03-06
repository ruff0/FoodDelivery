<section class="content">
    <!-- Small boxes (Stat box) -->
    <div class="row">
        <a href="/restro_delivery_order/">
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-green">
                    <i class="upper"><?php getownerordercountPendding(1); ?></i>
                    <div class="icon">
                        <img class="img-responsive" src="<?PHP echo base_url();  ?>assets/Restaurant_Owner/images/icon/car.png" alt="">  
                    </div>
                    <div class="inner">
                        <h4>DELIVERY</h4>
                    </div>
                </div>

                <div class="small-box-bottom bg-gray-light">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tbody>
                            <tr><td colspan="2">Today Sales: KD <?php echo $delivery_info["today_amount"]?></td></tr>
                            <tr><td colspan="2">Today Orders: <?php echo $delivery_info["today_orders"]?></td></tr>
                        </tbody></table>
                </div>
            </div>
        </a>
        <!-- ./col -->
        <a href="/restro_catering_order/">
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-yellow">
                    <i class="upper"><?php getownerordercountPendding(2); ?></i>
                    <div class="icon">
                        <img src="<?PHP echo base_url();  ?>assets/Restaurant_Owner/images/icon/man.png" class="img-responsive" alt=""/>
                    </div>
                    <div class="inner">
                        <h4>CATERING</h4>
                    </div>
                </div>

                <div class="small-box-bottom bg-gray-light">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tbody>
                            <tr><td colspan="2">Today Sales: KD <?php echo $catering_info["today_amount"]?></td></tr>
                            <tr><td colspan="2">Today Orders: <?php echo $catering_info["today_orders"]?></td></tr>
                        </tbody></table>
                </div>
            </div>
        </a>
        <!-- ./col -->
        <a href="/restro_reservation_order/">
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-red">
                    <i class="upper"><?php getownerordercountPendding(3); ?></i>
                    <div class="icon">
                        <img src="<?PHP echo base_url();  ?>assets/Restaurant_Owner/images/icon/food.png" class="img-responsive" alt=""/>
                    </div>
                    <div class="inner">
                        <h4>RESERVATION</h4>
                    </div>
                </div>

                <div class="small-box-bottom bg-gray-light">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tbody>
                            <tr><td colspan="2">Today Orders: <?php echo $reservation_info["today_orders"]?></td></tr>
                            <tr><td>&nbsp;</td></tr>
                        </tbody></table>
                </div>
            </div>
        </a>
        <!-- ./col -->
        <a href="/restro_pickup_order/">
            <div class="col-lg-3 col-xs-6">
                <!-- small box -->
                <div class="small-box bg-aqua">
                    <i class="upper"><?php getownerordercountPendding(4); ?></i>
                    <div class="icon">
                        <img src="<?PHP echo base_url();  ?>assets/Restaurant_Owner/images/icon/cock.png" class="img-responsive" alt=""/>
                    </div>
                    <div class="inner">
                        <h4>PICK UP</h4>
                    </div>
                </div>

                <div class="small-box-bottom bg-gray-light">
                    <table border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tbody>
                            <tr><td colspan="2">Today Sales: KD <?php echo $pickup_info["today_amount"]?></td></tr>
                            <tr><td colspan="2">Today Orders: <?php echo $pickup_info["today_orders"]?></td></tr>
                        </tbody></table>
                </div>
            </div>
        </a><!-- ./col -->
    </div><!-- /.row -->
    <!-- Main row -->
            </section>