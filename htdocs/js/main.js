$(document).ready(function() {
  function addStatus(status) {
    $(`<span class='badge badge-${status.class} mr-2' title='${status.hint}'>${status.text}</span>`)
      .appendTo('#statuses');
  }

  function addLibrary(library) {
    $(`<div id='library-${library.id}'></div>`)
      .appendTo('#libraries');
    $(`<h4 id='library-${library.id}-summary'>${library.id} : ${library.name}<span class='fa fa-redo text-muted float-right refresh-library' onclick='void(0)' data-library='${JSON.stringify(library)}'></span></h4>`)
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
    $(`<span id='library-${library.id}-${status.status}-summary' class='badge badge-pill badge-${statuses[status.status].class} ml-2 library-${library.id}-status toggle-sections' style='cursor:context-menu' onclick='void(0)' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}'>${status.count.toLocaleString()}<span class='fa fa-chevron-down ml-1 sections-collapse-icon'></span></span>`)
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
    $(`<div class='card-header'><span>${section.root_path}<span id='library-${library.id}-${status.status}-section-${section.id}-summary' class='badge badge-pill badge-dark ml-2 toggle-section-details' style='cursor:context-menu' onclick='void(0)' data-library='${JSON.stringify(library)}' data-status='${JSON.stringify(status)}' data-section='${JSON.stringify(section)}'>${section.count.toLocaleString()}<span class='fa fa-chevron-down ml-1 section-details-collapse-icon'></span></span></span></div>`)
      .appendTo(`#library-${library.id}-${status.status}-section-${section.id}`);
  }

  function addLibrarySectionDetails(library, status, section) {
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

  function syncStartStatus(selector) {
    $(selector).removeClass('fa-exclamation-triangle').addClass('fa-sync fa-spin');
  }

  function syncStopStatus(selector) {
    $(selector).on('animationiteration', function() {
      $(this).removeClass('fa-spin');
    });
  }

  function syncErrorStatus(selector) {
    $(selector).on('animationiteration', function() {
      $(this).removeClass('fa-sync fa-spin').addClass('fa-exclamation-triangle');
    });
  }

  function sectionOpen(selector) {
    $(selector).show();
  }

  function sectionClose(selector) {
    $(selector).hide();
  }

  function sectionOpenStatus(selector) {
    $(selector).removeClass('fa-chevron-down').addClass('fa-chevron-up');
  }

  function sectionCloseStatus(selector) {
      $(selector).removeClass('fa-chevron-up').addClass('fa-chevron-down');
  }

  $.get('query.php', {"function": "getStatuses"})
    .done(function(data) {
      statuses = $.parseJSON(data);
      $.each(statuses, function(statusID, status) {
        addStatus(status);
      });
    });

  syncStartStatus('#libraries-loading');
  $.getJSON('query.php', {"function": "getLibraries"})
    .done(function(libraries) {
      $.each(libraries, function(libraryID, library) {
        addLibrary(library);
        $.each(library.details, function(libraryDetailID, libraryDetail) {
          addLibraryDetail(library, libraryDetail);
        });
      });
      syncStopStatus('#libraries-loading');
    })
    .fail(function(jqxhr, textStatus, errorThrow) {
      console.log(`getLibraries failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
      syncErrorStatus('#libraries-loading');
    });

  $(document).on('click', '.toggle-sections', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    if ($(`#library-${library.id}-${status.status}-sections`).is(':hidden')) {
      sectionOpen(`#library-${library.id}-${status.status}-sections`);
      sectionOpenStatus($(this).children('.sections-collapse-icon'));
      if (!$(`#library-${library.id}-${status.status}-sections`).children('.library-status-section').length) {
        syncStartStatus('#libraries-loading');
        $.getJSON('query.php', {"function": "getLibrarySections", "library": library.id, "status": status.status})
          .done(function(librarySections) {
            $.each(librarySections, function(librarySectionID, librarySection) {
              addLibrarySection(library, status, librarySection);
            });
            syncStopStatus('#libraries-loading');
          })
          .fail(function(jqxhr, textStatus, errorThrown) {
            console.log(`getLibrarySections failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
            syncErrorStatus('#libraries-loading');
          });
      }
    } else if ($(`#library-${library.id}-${status.status}-sections`).is(':visible')) {
      sectionClose(`#library-${library.id}-${status.status}-sections`);
      sectionCloseStatus($(this).children('.sections-collapse-icon'));
    }
  });

  $(document).on('click', '.toggle-section-details', function() {
    var library = $(this).data('library');
    var status = $(this).data('status');
    var section = $(this).data('section');
    if (!$(`#library-${library.id}-${status.status}-section-${section.id}-details`).length) {
      syncStartStatus('#libraries-loading');
      addLibrarySectionDetails(library, status, section);
      sectionOpenStatus($(this).children('.section-details-collapse-icon'));
      $.getJSON('query.php', {"function": "getLibrarySectionDetails", "library": library.id, "status": status.status, "section": section.id})
        .done(function(librarySectionDetails) {
          $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
            addLibrarySectionDetail(library, status, section, librarySectionDetail);
          });
          syncStopStatus('#libraries-loading');
        })
        .fail(function(jqxhr, textStatus, errorThrow) {
          console.log(`getLibrarySectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
          syncErrorStatus('#libraries-loading');
        });
    } else if ($(`#library-${library.id}-${status.status}-section-${section.id}-details`).is(':hidden')) {
      sectionOpen(`#library-${library.id}-${status.status}-section-${section.id}-details`);
      sectionOpenStatus($(this).children('.section-details-collapse-icon'));
    } else if ($(`#library-${library.id}-${status.status}-section-${section.id}-details`).is(':visible')) {
      sectionClose(`#library-${library.id}-${status.status}-section-${section.id}-details`);
      sectionCloseStatus($(this).children('.section-details-collapse-icon'));
    }
  });

  $(document).on('click', '.refresh-library', function() {
    var library = $(this).data('library');
    syncStartStatus('#libraries-loading');
    $.getJSON('query.php', {"function": "getLibraryDetails", "library": library.id})
      .done(function(libraryDetails) {
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
            sectionOpen(`#library-${visibleSection.library.id}-${visibleSection.status.status}-sections`);
            sectionOpenStatus($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-summary`).children('.sections-collapse-icon'));
            $.getJSON('query.php', {"function": "getLibrarySections", "library": visibleSection.library.id, "status": visibleSection.status.status})
              .done(function(librarySections) {
                $.each(librarySections, function(librarySectionID, librarySection) {
                  addLibrarySection(visibleSection.library, visibleSection.status, librarySection);
                });
                $.each(visibleSection.details, function(visibleSectionDetailID, visibleSectionDetail) {
                  if ($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-section-${visibleSectionDetail.section.id}`).length) {
                    addLibrarySectionDetails(visibleSection.library, visibleSection.status, visibleSectionDetail.section);
                    sectionOpenStatus($(`#library-${visibleSection.library.id}-${visibleSection.status.status}-section-${visibleSectionDetail.section.id}-summary`).children('.section-details-collapse-icon'));
                    $.getJSON('query.php', {"function": "getLibrarySectionDetails", "library": visibleSection.library.id, "status": visibleSection.status.status, "section": visibleSectionDetail.section.id})
                      .done(function(librarySectionDetails) {
                        $.each(librarySectionDetails, function(librarySectionDetailID, librarySectionDetail) {
                          addLibrarySectionDetail(visibleSection.library, visibleSection.status, visibleSectionDetail.section, librarySectionDetail);
                        });
                      })
                     .fail(function(jqxhr, textStatus, errorThrow) {
                       console.log(`getLibrarySectionDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
                       syncErrorStatus('#libraries-loading');
                     });
                  }
                });
              })
              .fail(function(jqxhr, textStatus, errorThrow) {
                console.log(`getLibrarySections failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
                syncErrorStatus('#libraries-loading');
              });
          }
        });
        syncStopStatus('#libraries-loading');
      })
      .fail(function(jqxhr, textStatus, errorThrow) {
        console.log(`getLibraryDetails failed: ${jqxhr.status} (${jqxhr.statusText}), ${textStatus}, ${errorThrown}`);
        syncErrorStatus('#libraries-loading');
      });
  });
});
