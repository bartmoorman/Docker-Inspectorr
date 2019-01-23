<?php
require_once('inc/inspectorr.class.php');
$inspectorr = new Inspectorr(true, true, true, false);
?>
<!DOCTYPE html>
<html lang='en'>
  <head>
    <title>Inspectorr - Apps</title>
    <meta charset='utf-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1, shrink-to-fit=no'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css' integrity='sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB' crossorigin='anonymous'>
    <link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/bootswatch/4.1.1/darkly/bootstrap.min.css' integrity='sha384-ae362vOLHy2F1EfJtpMbNW0i9pNM1TP2l5O4VGYYiLJKsaejqVWibbP6BSf0UU5i' crossorigin='anonymous'>
    <link rel='stylesheet' href='//use.fontawesome.com/releases/v5.1.0/css/all.css' integrity='sha384-lKuwvrZot6UHsBSfcMvOkWwlCMgc0TaWr+30HWe3a4ltaBwTZhyTEggF5tJv8tbt' crossorigin='anonymous'>
  </head>
  <body>
<?php
include_once('header.php');
?>
    <div class='container'>
      <table class='table table-striped table-hover table-sm'>
        <thead>
          <tr>
            <th><button type='button' class='btn btn-sm btn-outline-success id-add'>Add</button></th>
            <th>App ID</th>
            <th>App Name</th>
            <th>Begin</th>
            <th>End</th>
          </tr>
        </thead>
        <tbody>
<?php
foreach ($inspectorr->getObjects('apps') as $app) {
  $begin = !empty($app['begin']) ? date('m/d/Y, h:i A', $app['begin']) : '&infin;';
  $end = !empty($app['end']) ? date('m/d/Y, h:i A', $app['end']) : '&infin;';
  $tableClass = $app['disabled'] ? 'text-warning' : 'table-default';
  echo "          <tr class='{$tableClass}'>" . PHP_EOL;
  echo "            <td><button type='button' class='btn btn-sm btn-outline-info id-details' data-app_id='{$app['app_id']}'>Details</button></td>" . PHP_EOL;
  echo "            <td>{$app['app_id']}</td>" . PHP_EOL;
  echo "            <td>{$app['name']}</td>" . PHP_EOL;
  echo "            <td>{$begin}</td>" . PHP_EOL;
  echo "            <td>{$end}</td>" . PHP_EOL;
  echo "          </tr>" . PHP_EOL;
}
?>
        </tbody>
      </table>
    </div>
    <div class='modal fade id-modal'>
      <div class='modal-dialog'>
        <div class='modal-content'>
          <form>
            <div class='modal-header'>
              <h5 class='modal-title'></h5>
            </div>
            <div class='modal-body'>
              <div class='form-row'>
                <div class='form-group col'>
                  <label>App Name <sup class='text-danger' data-toggle='tooltip' title='Required'>*</sup></label>
                  <input class='form-control' id='name' type='text' name='name' required>
                </div>
                <div class='form-group col'>
                  <label>Access Token <sup class='text-danger id-required' data-toggle='tooltip' title='Required'>*</sup></label>
                  <input class='form-control id-token' id='token' type='text' name='token' minlength='16' maxlength='16' pattern='[A-Za-z0-9]{16}' required>
                </div>
              </div>
              <div class='form-row'>
                <div class='form-group col'>
                  <label>Begin</label>
                  <input class='form-control' id='begin' type='datetime-local' name='begin'>
                </div>
                <div class='form-group col'>
                  <label>End</label>
                  <input class='form-control' id='end' type='datetime-local' name='end'>
                </div>
              </div>
            </div>
            <div class='modal-footer'>
              <button type='button' class='btn btn-outline-warning id-modify id-volatile'></button>
              <button type='button' class='btn btn-outline-danger mr-auto id-modify' data-action='delete'>Delete</button>
              <button type='button' class='btn btn-secondary' data-dismiss='modal'>Close</button>
              <button type='submit' class='btn id-submit'></button>
            </div>
          </form>
        </div>
      </div>
    </div>
    <script src='//code.jquery.com/jquery-3.3.1.min.js' integrity='sha384-tsQFqpEReu7ZLhBV2VZlAu7zcOV+rXbYlF2cqB8txI/8aZajjp4Bqd+V6D5IgvKT' crossorigin='anonymous'></script>
    <script src='//cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js' integrity='sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49' crossorigin='anonymous'></script>
    <script src='//maxcdn.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js' integrity='sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T' crossorigin='anonymous'></script>
    <script>
      $(document).ready(function() {
        $('[data-toggle="tooltip"]').tooltip();

        $('button.id-add').click(function() {
          $('h5.modal-title').text('Add App');
          $('form').removeData('app_id').data('func', 'createApp').trigger('reset');
          $('sup.id-required').addClass('d-none');
          $('input.id-token').prop('required', false).attr('placeholder', 'Will be generated if empty');
          $('button.id-modify').addClass('d-none').removeData('app_id');
          $('button.id-submit').removeClass('btn-info').addClass('btn-success').text('Add');
          $('div.id-modal').modal('toggle');
        });

        $('button.id-details').click(function() {
          $('h5.modal-title').text('App Details');
          $('form').removeData('app_id').data('func', 'updateApp').trigger('reset');
          $('sup.id-required').removeClass('d-none');
          $('input.id-token').removeAttr('placeholder').prop('required', true);
          $('button.id-modify').removeClass('d-none').removeData('app_id');
          $('button.id-submit').removeClass('btn-success').addClass('btn-info').text('Save');
          $.get('src/action.php', {"func": "getObjectDetails", "type": "app", "value": $(this).data('app_id')})
            .done(function(data) {
              if (data.success) {
                app = data.data;
                $('form').data('app_id', app.app_id);
                $('#name').val(app.name);
                $('#token').val(app.token);
                $('#begin').val(app.begin);
                $('#end').val(app.end);
                $('button.id-modify.id-volatile').data('action', app.disabled ? 'enable' : 'disable').text(app.disabled ? 'Enable' : 'Disable');
                $('button.id-modify').data('app_id', app.app_id);
                $('div.id-modal').modal('toggle');
              }
            })
            .fail(function(jqxhr, textStatus, errorThrown) {
              console.log(`getObjectDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
            });
        });

        $('button.id-modify').click(function() {
          if (confirm(`Want to ${$(this).data('action').toUpperCase()} app ${$(this).data('app_id')}?`)) {
            $.get('src/action.php', {"func": "modifyObject", "action": $(this).data('action'), "type": "app_id", "value": $(this).data('app_id')})
              .done(function(data) {
                if (data.success) {
                  location.reload();
                }
              })
              .fail(function(jqxhr, textStatus, errorThrown) {
                console.log(`modifyObject failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
              });
          }
        });

        $('form').submit(function(e) {
          e.preventDefault();
          $.post('src/action.php', {"func": $(this).data('func'), "app_id": $(this).data('app_id'), "name": $('#name').val(), "token": $('#token').val(), "begin": $('#begin').val(), "end": $('#end').val()})
            .done(function(data) {
              if (data.success) {
                location.reload();
              }
            })
            .fail(function(jqxhr, textStatus, errorThrown) {
              console.log(`${$(this).data('func')} failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
            });
        });

        $('button.id-nav').click(function() {
          location.href=$(this).data('href');
        });
      });
    </script>
  </body>
</html>