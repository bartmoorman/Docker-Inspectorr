class Complet {
  static addStatus(status) {
    $(`<span class='badge badge-${status.class} mr-2' title='${status.hint}'>${status.text}</span>`)
      .appendTo('#statuses');
  }

  static addLibrary(library) {
    $(`<div id='library-${library.id}'></div>`)
      .appendTo('#libraries');
    $(`<h4 id='library-${library.id}-summary'>${library.id} : ${library.name}<span class='fa fa-redo text-muted float-right refresh-library' onclick='void(0)' data-library='${JSON.stringify(library)}'></span></h4>`)
      .appendTo(`#library-${library.id}`);
    $(`<div id='library-${library.id}-progress' class='progress mb-3'></div>`)
      .appendTo(`#library-${library.id}`);
  }

  static removeLibraryDetail(library) {
    $(`.library-${library.id}-status.badge`).remove();
    $(`.library-${library.id}-status.progress-bar`).remove();
    $(`.library-${library.id}-section.card`).remove();
  }

  static addLibraryDetail(library, status) {
    $(`<span id='library-${library.id}-${status.status}-summary' class='badge badge-pill badge-${statuses[status.status].class} ml-2 library-${library.id}-status toggle-library-status-sections' style='cursor:context-menu' onclick='void(0)' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}'>${status.count.toLocaleString()}<span class='fa fa-chevron-down ml-1 library-status-sections-collapse-icon'></span></span>`)
      .appendTo(`#library-${library.id}-summary`);
    $(`<div class='progress-bar progress-bar-striped bg-${statuses[status.status].class} library-${library.id}-status' style='width:${status.count*100/library.count}%'></div>`)
      .appendTo(`#library-${library.id}-progress`);
    $(`<div id='library-${library.id}-${status.status}-sections' class='card border-${statuses[status.status].class} mb-3 library-${library.id}-section' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}'></div>`)
      .hide()
      .appendTo(`#library-${library.id}`);
    $(`<div class='card-header'><h5 class='text-${statuses[status.status].class} mb-0'>${statuses[status.status].text}<span class='badge badge-pill badge-dark ml-2'>${+(status.count*100/library.count).toFixed(2)}%</span></h5></div>`)
      .appendTo(`#library-${library.id}-${status.status}-sections`);
  }

  static addLibrarySection(library, status, section) {
    $(`<div id='library-${library.id}-${status.status}-section-${section.id}' class='library-status-section'></div>`)
      .appendTo(`#library-${library.id}-${status.status}-sections`);
    $(`<div class='card-header'><span>${section.root_path}<span id='library-${library.id}-${status.status}-section-${section.id}-summary' class='badge badge-pill badge-dark ml-2 toggle-library-status-section-details' style='cursor:context-menu' onclick='void(0)' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}' data-section='${JSON.stringify(section)}'>${section.count.toLocaleString()}<span class='fa fa-chevron-down ml-1 library-status-section-details-collapse-icon'></span></span></span></div>`)
      .appendTo(`#library-${library.id}-${status.status}-section-${section.id}`);
  }

  static addLibrarySectionDetails(library, status, section) {
    $(`<div id='library-${library.id}-${status.status}-section-${section.id}-details' class='card-body' data-section='${JSON.stringify(section)}'></div>`)
      .appendTo(`#library-${library.id}-${status.status}-section-${section.id}`);
  }

  static addLibrarySectionDetail(library, status, section, detail) {
    if (detail.type == 1) {
      $(`<p class='card-text text-muted mb-0'>${detail.title} (${detail.year})</p>`)
        .appendTo(`#library-${library.id}-${status.status}-section-${section.id}-details`);
    } else if (detail.type == 2) {
      $(`<p class='card-text text-muted mb-0'>${detail.show_title} - s${detail.season.toString().padStart(2, 0)}e${detail.episode.toString().padStart(2, 0)} - ${detail.episode_title}</p>`)
        .appendTo(`#library-${library.id}-${status.status}-section-${section.id}-details`);
    }
  }

  static syncStartStatus(selector) {
    $(selector).removeClass('fa-exclamation-triangle').addClass('fa-sync fa-spin');
  }

  static syncStopStatus(selector) {
    $(selector).on('animationiteration', function() {
      $(this).removeClass('fa-spin');
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
