$(document).ready(function() {
  function addLibrary(library) {
    $(`<div id='library-${library.id}'></div>`)
      .appendTo('#libraries');
    $(`<h4 id='library-${library.id}-summary'>${library.id} : ${library.name}<span class='fas fa-redo text-muted float-right refresh-library' data-library='${JSON.stringify(library)}'></span></h4>`)
      .appendTo(`#library-${library.id}`);
    $(`<div id='library-${library.id}-progress' class='progress mb-3'></div>`)
      .appendTo(`#library-${library.id}`);
  }

  function removeLibraryDetail(library) {
    $(`.library-${library.id}-status.badge`).remove();
    $(`.library-${library.id}-status.progress-bar`).remove();
    $(`.library-${library.id}-section.card`).remove();
  }

  function addLibraryDetail(library, status) {
    $(`<span class='badge badge-pill badge-${statuses[status.status].class} ml-2 library-${library.id}-status toggle-sections' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}'>${status.count.toLocaleString()}</span>`)
      .appendTo(`#library-${library.id}-summary`);
    $(`<div class='progress-bar progress-bar-striped bg-${statuses[status.status].class} library-${library.id}-status' style='width:${status.count*100/library.count}%'></div>`)
      .appendTo(`#library-${library.id}-progress`);
    $(`<div id='library-${library.id}-${status.status}-sections' class='card border-${statuses[status.status].class} mb-3 library-${library.id}-section' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}'></div>`)
      .hide()
      .appendTo(`#library-${library.id}`);
    $(`<div class='card-header'><h5 class='text-${statuses[status.status].class} mb-0'>${statuses[status.status].text}<span class='badge badge-pill badge-dark ml-2'>${+(status.count*100/library.count).toFixed(2)}%</span></h5></div>`)
      .appendTo(`#library-${library.id}-${status.status}-sections`);
  }

  function addLibrarySection(library, status, section) {
    $(`<div id='library-${library.id}-${status.status}-section-${section.id}' class='library-status-section'></div>`)
      .appendTo(`#library-${library.id}-${status.status}-sections`);
    $(`<div class='card-header'><span class='close toggle-section-details' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}' data-section='${JSON.stringify(section)}'>&plus;</span><span>${section.root_path}<span class='badge badge-pill badge-dark ml-2'>${section.count.toLocaleString()}</span></span></div>`)
      .appendTo(`#library-${library.id}-${status.status}-section-${section.id}`);
  }

  function addLibrarySectionStatus(library, status, section) {
    $(`<div id='library-${library.id}-${status.status}-section-${section.id}-details' class='card-body' data-section='${JSON.stringify(section)}'></div>`)
      .appendTo(`#library-${library.id}-${status.status}-section-${section.id}`);
  }

  function addLibrarySectionDetail(library, status, section, detail) {
    if (detail.type == 1) {
      $(`<p class='card-text text-muted mb-0'>${detail.title} (${detail.year})</p>`)
        .appendTo(`#library-${library.id}-${status.status}-section-${section.id}-details`);
    } else if (detail.type == 2) {
      $(`<p class='card-text text-muted mb-0'>${detail.show_title} - s${detail.season}e${detail.episode} - ${detail.episode_title}</p>`)
        .appendTo(`#library-${library.id}-${status.status}-section-${section.id}-details`);
    }
  }

  $.get('query.php', {"function": "getStatuses"})
    .done(function(data) {
      statuses = $.parseJSON(data);
    });

  $('#libraries-loading').addClass('fa-spin');
  $.get('query.php', {"function": "getLibraries"})
    .done(function(data) {
      var libraries = $.parseJSON(data);
      $.each(libraries, function(libraryID, library) {
        addLibrary(library);
        $.each(library.details, function(libraryDetailID, libraryDetail) {
          addLibraryDetail(library, libraryDetail);
        });
      });
      $('#libraries-loading').on('animationiteration', function() {
        $(this).removeClass('fa-spin');
      });
    });

  $(document).on('click', 'span.toggle-sections', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    if ($(`#library-${library.id}-${status.status}-sections`).is(':hidden')) {
      $(`#library-${library.id}-${status.status}-sections`).show();
      if (!$(`#library-${library.id}-${status.status}-sections`).children('.library-status-section').length) {
        $('#libraries-loading').addClass('fa-spin');
        $.get('query.php', {"function": "getLibrarySections", "library": library.id, "status": status.status})
          .done(function(data) {
            var librarySections = $.parseJSON(data);
            $.each(librarySections, function(librarySectionID, librarySection) {
              addLibrarySection(library, status, librarySection);
            });
            $('#libraries-loading').on('animationiteration', function() {
              $(this).removeClass('fa-spin');
            });
          });
      }
    } else if ($(`#library-${library.id}-${status.status}-sections`).is(':visible')) {
      $(`#library-${library.id}-${status.status}-sections`).hide();
    }
  });

  $(document).on('click', 'span.toggle-section-details', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    var section = $(this).data('section');
    if (!$(`#library-${library.id}-${status.status}-section-${section.id}-details`).length) {
      $('#libraries-loading').addClass('fa-spin');
      addLibrarySectionStatus(library, status, section);
      $.get('query.php', {"function": "getLibrarySectionDetails", "library": library.id, "status": status.status, "section": section.id})
        .done(function(data) {
          var librarySectionDetails = $.parseJSON(data);
          $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
            addLibrarySectionDetail(library, status, section, librarySectionDetail);
          });
          $('#libraries-loading').on('animationiteration', function() {
            $(this).removeClass('fa-spin');
          });
        });
      $(this).html('&minus;');
    } else if ($(`#library-${library.id}-${status.status}-section-${section.id}-details`).is(':hidden')) {
      $(`#library-${library.id}-${status.status}-section-${section.id}-details`).show();
      $(this).html('&minus;');
    } else if ($(`#library-${library.id}-${status.status}-section-${section.id}-details`).is(':visible')) {
      $(`#library-${library.id}-${status.status}-section-${section.id}-details`).hide();
      $(this).html('&plus;');
    }
  });

  $(document).on('click', 'span.refresh-library', function() {
    var library = $(this).data('library');
    $('#libraries-loading').addClass('fa-spin');
    $.get('query.php', {"function": "getLibraryDetails", "library": library.id})
      .done(function(data) {
        var libraryDetails = $.parseJSON(data);
        var visibleSections = [];
        $(`.library-${library.id}-section.card:visible`).each(function() {
          var visibleSection = [];
          visibleSection.details = [];
          visibleSection.library = $(this).data('library');
          visibleSection.status = $(this).data('status');
          $(this).children('.library-status-section').children('.card-body:visible').each(function() {
            var visibleSectionDetails = [];
            visibleSectionDetails.section = $(this).data('section')
            visibleSection.details.push(visibleSectionDetails);
          });
          visibleSections.push(visibleSection);
        });
        removeLibraryDetail(library);
        $.each(libraryDetails, function(libraryDetailID, libraryDetail) {
          addLibraryDetail(library, libraryDetail);
        });
        $.each(visibleSections, function(visibleSectionID, visibleSection) {
          if ($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-sections`).length) {
            $(`#library-${visibleSection.library.id}-${visibleSection.status.status}-sections`).show();
            $.get('query.php', {"function": "getLibrarySections", "library": visibleSection.library.id, "status": visibleSection.status.status})
              .done(function(data) {
                var librarySections = $.parseJSON(data);
                $.each(librarySections, function(librarySectionID, librarySection) {
                  addLibrarySection(visibleSection.library, visibleSection.status, librarySection);
                });
                $.each(visibleSection.details, function(visibleSectionDetailID, visibleSectionDetail) {
                  if ($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-section-${visibleSectionDetail.section.id}`).length) {
                    addLibrarySectionStatus(visibleSection.library, visibleSection.status, visibleSectionDetail.section);
                    $.get('query.php', {"function": "getLibrarySectionDetails", "library": visibleSection.library.id, "status": visibleSection.status.status, "section": visibleSectionDetail.section.id})
                      .done(function(data) {
                        var librarySectionDetails = $.parseJSON(data);
                        $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
                          addLibrarySectionDetail(visibleSection.library, visibleSection.status, visibleSectionDetail.section, librarySectionDetail);
                        });
                      });
                  }
                });
              });
          }
        });
        $('#libraries-loading').on('animationiteration', function() {
          $(this).removeClass('fa-spin');
        });
      });
  });
});
