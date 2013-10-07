YUI.add('moodle-block_proctoru-regreport', function (Y, NAME) {

M.block_proctoru = M.block_proctoru || {};
M.block_proctoru.regreport = {
  init: function(data) {

    var table = new Y.DataTable({
        columns:    ['firstname','lastname','idnumber', 'status', 'role'],
        data:       data,
        sortable:   true,
        scrollable: 'y',
        height:     '600px'
    });
    
    table.render('#report');
  }
};

}, '@VERSION@', {"requires": ["datatable", "datatable-scroll"]});
