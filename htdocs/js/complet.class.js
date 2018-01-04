class Complet {
  static addStatus(status) {
    $(`<span class='badge badge-${status.class} mr-2' title='${status.hint}'>${status.text}</span>`)
      .appendTo('#statuses');
  }

  static addLibrary(library) {
    $(`<div id='library-${library.id}'></div>`)
      .appendTo('#libraries');
    $(`<div id='library-${library.id}-summary'><h4>${library.id} : ${library.name}</h4></div>`)
      .appendTo(`#library-${library.id}`);
    $(`<div id='library-${library.id}-progress' class='progress mb-3'></div>`)
      .appendTo(`#library-${library.id}`);
  }

  static removeAllLibraries() {
    $('#libraries > div').each(function() {
      $(this).remove();
    });
  }

  static addLibraryDetail(library, status) {
    $(`<span class='badge badge-pill badge-${statuses[status.status].class} toggle-status-sections ml-2' style='cursor:context-menu' onclick='void(0)' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}'>${status.count.toLocaleString()}<span class='fa fa-chevron-down ml-1'></span></span>`)
      .appendTo(`#library-${library.id}-summary > h4`);
    $(`<div class='progress-bar progress-bar-striped bg-${statuses[status.status].class}' style='width:${status.count*100/library.count}%'></div>`)
      .appendTo(`#library-${library.id}-progress`);
    $(`<div id='library-${library.id}-status-${status.status}' class='card border-${statuses[status.status].class} mb-3'></div>`)
      .hide()
      .appendTo(`#library-${library.id}`);
    $(`<div id='library-${library.id}-status-${status.status}-summary' class='card-header'><h5 class='text-${statuses[status.status].class} mb-0'>${statuses[status.status].text}<span class='badge badge-pill badge-dark ml-2'>${+(status.count*100/library.count).toFixed(2)}%</span></h5></div>`)
      .appendTo(`#library-${library.id}-status-${status.status}`);
  }

  static addLibrarySection(library, status, section) {
    $(`<div id='library-${library.id}-status-${status.status}-section-${section.id}'></div>`)
      .appendTo(`#library-${library.id}-status-${status.status}`);
    $(`<div id='library-${library.id}-status-${status.status}-section-${section.id}-summary' class='card-header'>${section.root_path}<span class='badge badge-pill badge-dark toggle-status-section-details ml-2' style='cursor:context-menu' onclick='void(0)' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}' data-section='${JSON.stringify(section)}'>${section.count.toLocaleString()}<span class='fa fa-chevron-down ml-1'></span></span></div>`)
      .appendTo(`#library-${library.id}-status-${status.status}-section-${section.id}`);
  }

  static addLibrarySectionDetails(library, status, section) {
    $(`<div id='library-${library.id}-status-${status.status}-section-${section.id}-details' class='card-body'></div>`)
      .appendTo(`#library-${library.id}-status-${status.status}-section-${section.id}`);
  }

  static addLibrarySectionDetail(library, status, section, detail) {
    if (detail.type == 1) {
      $(`<p class='card-text text-muted mb-0'>${detail.title} (${detail.year})</p>`)
        .appendTo(`#library-${library.id}-status-${status.status}-section-${section.id}-details`);
    } else if (detail.type == 2) {
      $(`<p class='card-text text-muted mb-0'>${detail.show_title} - s${detail.season.toString().padStart(2, 0)}e${detail.episode.toString().padStart(2, 0)} - ${detail.episode_title}</p>`)
        .appendTo(`#library-${library.id}-status-${status.status}-section-${section.id}-details`);
    }
  }

  static syncStartStatus(selector) {
    $(selector).removeClass('fa-exclamation-triangle refresh-libraries').addClass('fa-sync fa-spin');
  }

  static syncStopStatus(selector) {
    $(selector).on('animationiteration', function() {
      $(this).removeClass('fa-spin').addClass('refresh-libraries');
    });
  }

  static syncErrorStatus(selector) {
    $(selector).on('animationiteration', function() {
      $(this).removeClass('fa-sync fa-spin').addClass('fa-exclamation-triangle');
    });
  }

  static sectionOpen(selector) {
    $(selector).show();
  }

  static sectionClose(selector) {
    $(selector).hide();
  }

  static sectionOpenStatus(selector) {
    $(selector).removeClass('fa-chevron-down').addClass('fa-chevron-up');
  }

  static sectionCloseStatus(selector) {
    $(selector).removeClass('fa-chevron-up').addClass('fa-chevron-down');
  }
}
