<?php 
	//$this->erp->print_arrays("yes");
?>
<script>
    $(document).ready(function () {
        var oTable = $('#TOData').dataTable({
            "aaSorting": [[1, "desc"]],
            "aLengthMenu": [[10, 25, 50, 100, -1], [10, 25, 50, 100, "<?= lang('all') ?>"]],
            "iDisplayLength": <?= $Settings->rows_per_page ?>,
            'bProcessing': true, 'bServerSide': true,
			//
			"bStateSave": true,
			"fnStateSave": function (oSettings, oData) {
				localStorage.setItem('DataTables_' + window.location.pathname, JSON.stringify(oData));
			},
			"fnStateLoad": function (oSettings) {
				var data = localStorage.getItem('DataTables_' + window.location.pathname);
				return JSON.parse(data);
			},
            'sAjaxSource': '<?=site_url('transfers/getInTransfers' . ($warehouse_id ? '/' . $warehouse_id : '')) ?>',
            'fnServerData': function (sSource, aoData, fnCallback) {
                aoData.push({
                    "name": "<?= $this->security->get_csrf_token_name() ?>",
                    "value": "<?= $this->security->get_csrf_hash() ?>"
                });
                $.ajax({'dataType': 'json', 'type': 'POST', 'url': sSource, 'data': aoData, 'success': fnCallback});
            },
            "aoColumns": [{
                "bSortable": false,
                "mRender": checkbox
            }, {"mRender": fld}, null, null, null, {"mRender": currencyFormat}, {"bSortable": false}],
            'fnRowCallback': function (nRow, aData, iDisplayIndex) {
                var oSettings = oTable.fnSettings();
                nRow.id = aData[0];
                nRow.className = "transfer_link";
                return nRow;
            },
            "fnFooterCallback": function (nRow, aaData, iStart, iEnd, aiDisplay) {
                var row_total = 0;
                for (var i = 0; i < aaData.length; i++) {
                    row_total += parseFloat(aaData[aiDisplay[i]][5]);
                }
                var nCells = nRow.getElementsByTagName('th');
                nCells[5].innerHTML = currencyFormat(formatMoney(row_total));
            }
        }).fnSetFilteringDelay().dtFilter([
            {column_number: 1, filter_default_label: "[<?=lang('date');?> (yyyy-mm-dd)]", filter_type: "text", data: []},
            {column_number: 2, filter_default_label: "[<?=lang('ref_no');?>]", filter_type: "text", data: []},
            {
                column_number: 3,
                filter_default_label: "[<?=lang("warehouse").' ('.lang('from').')';?>]",
                filter_type: "text", data: []
            },
            {
                column_number: 4,
                filter_default_label: "[<?=lang("warehouse").' ('.lang('to').')';?>]",
                filter_type: "text", data: []
            },
        ], "footer");
    });
</script>
<?php
    echo form_open('transfers/transfer_actions/'.($warehouse_id ? $warehouse_id : ''), 'id="action-form"');
?>
<div class="box">
    <div class="box-header">
        <h2 class="blue"><i class="fa-fw fa fa-star-o"></i><?= lang('list_transfer'); ?></h2>

        <div class="box-icon">
            <ul class="btn-tasks">
            <?php if ($Owner || $Admin || $GP['transfers-add'] || $GP['transfers-import'] || $GP['transfers-export'] || $GP['transfers-combine_pdf'] || $GP['transfers-delete']) { ?>
                <li class="dropdown">
                    <a data-toggle="dropdown" class="dropdown-toggle" href="#">
                        <i class="icon fa fa-tasks tip"  data-placement="left" title="<?= lang("actions") ?>"></i>
                    </a>
                    <ul class="dropdown-menu pull-right tasks-menus" role="menu" aria-labelledby="dLabel">
                        <?php if ($Owner || $Admin || $GP['transfers-add']) { ?>
							<li>
								<a href="<?= site_url('transfers/add') ?>">
									<i class="fa fa-plus-circle"></i> <?= lang('add_transfer') ?>
								</a>
							</li>
						<?php } ?>
						<?php if ($Owner || $Admin || $GP['transfers-import']) { ?>
							<li>
								<a href="<?= site_url('transfers/transfer_by_csv'); ?>">
									<i class="fa fa-plus-circle"></i><span class="text"> <?= lang('add_transfer_by_csv'); ?></span>
								</a>
							</li>
                        <?php } ?>
						<?php if($Owner || $Admin || $GP['transfers-export']) { ?>
							<li>
								<a href="#" id="excel" data-action="export_excel">
									<i class="fa fa-file-excel-o"></i> <?= lang('export_to_excel') ?>
								</a>
							</li>
							<li>
								<a href="#" id="pdf" data-action="export_pdf">
									<i class="fa fa-file-pdf-o"></i> <?= lang('export_to_pdf') ?>
								</a>
							</li>
						<?php }?>
						<?php if($Owner || $Admin || $GP['transfers-combine_pdf']) { ?>
							<li>
								<a href="#" id="combine" data-action="combine">
									<i class="fa fa-file-pdf-o"></i> <?=lang('combine_to_pdf')?>
								</a>
							</li>
						<?php }?>
						<?php if ($Owner || $Admin || $GP['transfers-delete']) { ?>
							<li class="divider"></li>
							<li>
								<a href="#" class="bpo" title="<?= $this->lang->line("delete_transfers") ?>"
								 data-content="<p><?= lang('r_u_sure') ?></p><button type='button' class='btn btn-danger' id='delete' data-action='delete'><?= lang('i_m_sure') ?></a> <button class='btn bpo-close'><?= lang('no') ?></button>"
								 data-html="true" data-placement="left">
								 <i class="fa fa-trash-o"></i> <?= lang('delete_transfers') ?>
								</a>
							</li>
						<?php } ?>
                 </ul>
             </li>
            <?php } ?>
            </ul>
        </div>
    </div>
    <div class="box-content">
        <div class="row">
            <div class="col-lg-12">
				
                <p class="introtext"><?= lang('list_results'); ?></p>

                <div class="table-responsive">
                    <table id="TOData" cellpadding="0" cellspacing="0" border="0"
                           class="table table-bordered table-condensed table-hover table-striped">
                        <thead>
                        <tr class="active">
                            <th class="col-sm-1" style="text-align: center;">
                                <input class="checkbox checkft" type="checkbox" name="check"/>
                            </th>
                            <th class="col-sm-2"><?= lang("date"); ?></th>
                            <th class="col-sm-2"><?= lang("ref_no"); ?></th>
                            <th class="col-sm-2"><?= lang("warehouse") . ' (' . lang('from') . ')'; ?></th>
                            <th class="col-sm-2"><?= lang("warehouse") . ' (' . lang('to') . ')'; ?></th>
                            <th class="col-sm-1"><?= lang("quantity"); ?></th>
                            <th class="col-sm-2"><?= lang("actions"); ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td colspan="10" class="dataTables_empty"><?= lang('loading_data_from_server'); ?></td>
                        </tr>
                        </tbody>
                        <tfoot class="dtFilter">
                        <tr class="active">
                            <th style="text-align: center;">
                                <input class="checkbox checkft" type="checkbox" name="check"/>
                            </th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th></th>
                            <th style="text-align: center;"><?= lang("actions"); ?></th>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<div style="display: none;">
    <input type="hidden" name="form_action" value="" id="form_action"/>
    <?= form_submit('performAction', 'performAction', 'id="action-form-submit"') ?>
</div>
<?= form_close() ?>