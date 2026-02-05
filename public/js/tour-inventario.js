/**
 * Tour de onboarding para el Sistema de Inventario Documental
 * Usa Driver.js - https://driverjs.com
 *
 * Tours disponibles:
 * 1. Dashboard (Inicio)
 * 2. Lista de Registros de Inventario (FUID)
 * 3. Crear Registro de Inventario
 * 4. Lista de Actos Administrativos
 * 5. Crear Acto Administrativo
 */

document.addEventListener("DOMContentLoaded", function () {
    const pathname = window.location.pathname;

    // Determinar en que pagina estamos
    const isAdminDashboard = pathname === "/admin" || pathname === "/admin/";
    const isInventoryList =
        pathname.endsWith("inventory-records") ||
        pathname.endsWith("inventory-records/");
    const isInventoryCreate = pathname.includes("inventory-records/create");
    const isActsList =
        pathname.endsWith("administrative-acts") ||
        pathname.endsWith("administrative-acts/");
    const isActsCreate = pathname.includes("administrative-acts/create");

    // Solo ejecutar en las paginas relevantes
    if (
        !isAdminDashboard &&
        !isInventoryList &&
        !isInventoryCreate &&
        !isActsList &&
        !isActsCreate
    ) {
        return;
    }

    // Driver.js se carga via CDN, acceder desde window
    const driverFn = window.driver.js.driver;

    // ========================================================
    // Marcar elementos del menu para el tour del dashboard
    // ========================================================
    if (isAdminDashboard) {
        var menuLinks = document.querySelectorAll(".fi-sidebar-nav a");
        menuLinks.forEach(function (link) {
            var text = link.textContent.trim();
            var href = link.href || "";

            if (
                text.includes("Registros de Inventario") ||
                href.includes("inventory-records")
            ) {
                link.setAttribute("data-tour", "menu-inventario");
            }

            if (
                text.includes("Actos Administrativos") ||
                href.includes("administrative-acts")
            ) {
                link.setAttribute("data-tour", "menu-actos");
            }

            if (
                text.includes("Series Documentales") ||
                href.includes("documentary-series")
            ) {
                link.setAttribute("data-tour", "menu-series");
            }

            if (
                text.includes("Subseries") ||
                href.includes("documentary-subseries")
            ) {
                link.setAttribute("data-tour", "menu-subseries");
            }

            if (text.includes("Usuarios") || href.includes("users")) {
                link.setAttribute("data-tour", "menu-usuarios");
            }
        });
    }

    // ========================================================
    // TOUR 1: DASHBOARD (INICIO)
    // ========================================================
    if (isAdminDashboard) {
        var tourDashboard = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "Bienvenido al Inventario Documental",
                        description:
                            "Este sistema te permite gestionar el inventario documental de tu entidad: registros FUID, series, subseries y actos administrativos. Te explicaremos paso a paso como funciona.",
                    },
                },
                {
                    element: "[data-tour='menu-inventario']",
                    popover: {
                        title: "Registros de Inventario (FUID)",
                        description:
                            "Aqui puedes ver, crear y gestionar todos los registros del Formato Unico de Inventario Documental. Es el modulo principal del sistema.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='menu-actos']",
                    popover: {
                        title: "Actos Administrativos",
                        description:
                            "Aqui registras los actos administrativos: resoluciones, decretos, circulares y demas documentos oficiales de tu entidad.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='menu-series']",
                    popover: {
                        title: "Series Documentales",
                        description:
                            "Las series documentales agrupan los documentos segun la Tabla de Retencion Documental (TRD). Son categorias principales como 'Contratos', 'Actas', etc.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='menu-subseries']",
                    popover: {
                        title: "Subseries Documentales",
                        description:
                            "Las subseries son subdivisiones de las series. Por ejemplo, dentro de 'Contratos' puedes tener 'Contratos de Prestacion de Servicios'.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='help-button-dashboard']",
                    popover: {
                        title: "¿Necesitas ayuda?",
                        description:
                            "Puedes ver este tutorial de nuevo cuando quieras haciendo clic en este boton.",
                        side: "bottom",
                    },
                },
                {
                    popover: {
                        title: "¡Listo para comenzar!",
                        description:
                            "Ya conoces lo basico. Dirigete a 'Registros de Inventario' para empezar a gestionar tus documentos.",
                    },
                },
            ],
        });

        window.tourDashboard = tourDashboard;

        var tourDashboardShown = localStorage.getItem(
            "tourInventarioDashboardShown",
        );
        if (!tourDashboardShown) {
            setTimeout(function () {
                tourDashboard.drive();
                localStorage.setItem("tourInventarioDashboardShown", "true");
            }, 1000);
        }
    }

    // ========================================================
    // TOUR 2: LISTA DE REGISTROS DE INVENTARIO (FUID)
    // ========================================================
    if (isInventoryList) {
        var tourInventoryList = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "Registros de Inventario Documental",
                        description:
                            "Aqui puedes ver todos los registros del Formato Unico de Inventario Documental (FUID). Cada fila es un expediente o unidad documental registrada.",
                    },
                },
                {
                    element: ".fi-ta-table",
                    popover: {
                        title: "Tu tabla de registros",
                        description:
                            "Cada fila muestra: codigo de referencia, titulo, oficina productora, serie documental, numero de caja/carpeta y fechas. Puedes hacer clic en una fila para ver el detalle.",
                        side: "top",
                    },
                },
                {
                    element: ".fi-ta-header-ctn",
                    popover: {
                        title: "Buscar y filtrar",
                        description:
                            "Usa la barra de busqueda para encontrar registros por titulo o codigo. Tambien puedes usar los filtros para buscar por oficina productora, serie documental, soporte, etc.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='create-button-inventory']",
                    popover: {
                        title: "Crear nuevo registro",
                        description:
                            "Haz clic aqui para registrar una nueva unidad documental en el inventario. Se abrira un formulario con todos los campos del FUID.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='download-template-inventory']",
                    popover: {
                        title: "Descargar plantilla Excel",
                        description:
                            "Descarga una plantilla de Excel con el formato correcto para importar registros masivamente. Muy util cuando tienes muchos registros que cargar.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='import-button-inventory']",
                    popover: {
                        title: "Importar desde Excel",
                        description:
                            "Sube un archivo Excel con los datos de tus registros para cargarlos al sistema de forma masiva. Usa la plantilla para asegurar el formato correcto.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='export-button-inventory']",
                    popover: {
                        title: "Exportar a Excel",
                        description:
                            "Exporta todos los registros visibles (con los filtros aplicados) a un archivo Excel para reportes o respaldos.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='help-button-inventory']",
                    popover: {
                        title: "¿Necesitas ayuda?",
                        description:
                            "Puedes ver este tutorial de nuevo en cualquier momento haciendo clic aqui.",
                        side: "bottom",
                    },
                },
                {
                    popover: {
                        title: "¡Listo!",
                        description:
                            "Ya conoces la lista de registros. Crea tu primer registro o explora los existentes. Recuerda que puedes duplicar, editar o eliminar registros desde las acciones de cada fila.",
                    },
                },
            ],
        });

        window.tourInventoryList = tourInventoryList;

        var tourInventoryListShown = localStorage.getItem(
            "tourInventoryListShown",
        );
        if (!tourInventoryListShown) {
            setTimeout(function () {
                tourInventoryList.drive();
                localStorage.setItem("tourInventoryListShown", "true");
            }, 1000);
        }
    }

    // ========================================================
    // TOUR 3: CREAR REGISTRO DE INVENTARIO (FUID)
    // ========================================================
    if (isInventoryCreate) {
        var tourInventoryCreate = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "Crear Registro de Inventario (FUID)",
                        description:
                            "Este formulario sigue el Formato Unico de Inventario Documental. Completa cada seccion para registrar una unidad documental. Los campos con * son obligatorios.",
                    },
                },
                {
                    element: "[data-tour='inv-oficina-productora']",
                    popover: {
                        title: "Paso 1: Oficina Productora",
                        description:
                            "Selecciona la oficina que produjo o gestiona el documento. Si eres usuario normal, ya esta seleccionada tu oficina. El codigo de oficina se llena automaticamente.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='inv-objeto']",
                    popover: {
                        title: "Paso 2: Objeto del Inventario",
                        description:
                            "Selecciona el proposito del inventario: Transferencia primaria, Transferencia secundaria, Inventario individual de entrega, etc. Indica POR QUE se esta inventariando.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='inv-serie']",
                    popover: {
                        title: "Paso 3: Serie Documental",
                        description:
                            "Selecciona la serie documental segun la Tabla de Retencion Documental (TRD). Ejemplo: 'Contratos', 'Actas', 'Informes'. Al seleccionar la serie, se filtraran las subseries disponibles.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='inv-subserie']",
                    popover: {
                        title: "Paso 4: Subserie Documental",
                        description:
                            "Selecciona la subserie correspondiente. Las opciones dependen de la serie que elegiste en el paso anterior.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='inv-titulo']",
                    popover: {
                        title: "Paso 5: Nombre de la Unidad Documental",
                        description:
                            "Escribe el nombre que identifica esta unidad documental. Ejemplo: 'Contrato de prestacion de servicios No. 123 de 2025' o 'Acta de Comite No. 5'.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='inv-fechas']",
                    popover: {
                        title: "Paso 6: Fechas Extremas",
                        description:
                            "Indica la fecha inicial y final del documento. Si el documento no tiene fecha, desactiva el toggle y aparecera 'S.F.' (Sin Fecha). La fecha inicial es la del documento mas antiguo y la final la del mas reciente.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='inv-ubicacion']",
                    popover: {
                        title: "Paso 7: Ubicacion Fisica",
                        description:
                            "Indica donde esta guardado el documento fisicamente: numero de caja, carpeta, tomo/legajo y cantidad de folios. El numero de carpeta se reinicia en 1 con cada nueva caja.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='inv-soporte']",
                    popover: {
                        title: "Paso 8: Soporte",
                        description:
                            "Indica el tipo de soporte: papel, electronico o ambos. Si hay medios adicionales (CD, DVD, microfilm, etc.), seleccionalos e indica la cantidad.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='inv-adjuntos']",
                    popover: {
                        title: "Paso 9: Archivo Digitalizado",
                        description:
                            "Si tienes una version digitalizada (escaneada) del documento, subela aqui en formato PDF. Puedes subir varios archivos. Maximo 20MB por archivo.",
                        side: "top",
                    },
                },
                {
                    element: ".fi-form-actions",
                    popover: {
                        title: "Paso 10: Guardar el Registro",
                        description:
                            "Cuando hayas completado todos los campos, haz clic en 'Crear' para guardar el registro. El sistema generara automaticamente un codigo de referencia unico.",
                        side: "top",
                    },
                },
                {
                    popover: {
                        title: "¡Ya estas listo!",
                        description:
                            "Completa el formulario paso a paso. Si tienes muchos registros, recuerda que puedes usar la importacion masiva desde Excel en la lista de registros.",
                    },
                },
            ],
        });

        window.tourInventoryCreate = tourInventoryCreate;

        var tourInventoryCreateShown = localStorage.getItem(
            "tourInventoryCreateShown",
        );
        if (!tourInventoryCreateShown) {
            setTimeout(function () {
                tourInventoryCreate.drive();
                localStorage.setItem("tourInventoryCreateShown", "true");
            }, 1000);
        }
    }

    // ========================================================
    // TOUR 4: LISTA DE ACTOS ADMINISTRATIVOS
    // ========================================================
    if (isActsList) {
        var tourActsList = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "Actos Administrativos",
                        description:
                            "Aqui gestionas los actos administrativos de tu entidad: resoluciones, decretos, acuerdos, circulares y demas documentos oficiales. Cada registro guarda el acto con su clasificacion y archivos adjuntos.",
                    },
                },
                {
                    element: ".fi-ta-table",
                    popover: {
                        title: "Tu lista de actos",
                        description:
                            "Cada fila muestra: numero de consecutivo, tipo de acto, objeto/asunto, unidad organizacional y fecha. Puedes hacer clic en una fila para ver el detalle completo.",
                        side: "top",
                    },
                },
                {
                    element: ".fi-ta-header-ctn",
                    popover: {
                        title: "Buscar y filtrar",
                        description:
                            "Busca actos por consecutivo o asunto. Usa los filtros para buscar por tipo de acto, unidad organizacional o rango de fechas.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='create-button-acts']",
                    popover: {
                        title: "Crear nuevo acto",
                        description:
                            "Haz clic aqui para registrar un nuevo acto administrativo. Se abrira un formulario donde podras ingresar toda la informacion y adjuntar los documentos PDF.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='download-template-acts']",
                    popover: {
                        title: "Descargar plantilla Excel",
                        description:
                            "Descarga una plantilla con el formato correcto para importar actos administrativos de forma masiva.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='import-button-acts']",
                    popover: {
                        title: "Importar desde Excel",
                        description:
                            "Sube un archivo Excel para cargar multiples actos administrativos al sistema de una sola vez.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='export-button-acts']",
                    popover: {
                        title: "Exportar a Excel",
                        description:
                            "Descarga los actos administrativos en formato Excel para reportes o respaldos.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='help-button-acts']",
                    popover: {
                        title: "¿Necesitas ayuda?",
                        description:
                            "Puedes ver este tutorial de nuevo en cualquier momento haciendo clic aqui.",
                        side: "bottom",
                    },
                },
                {
                    popover: {
                        title: "¡Listo!",
                        description:
                            "Ya conoces el modulo de actos administrativos. Crea tu primer acto o explora los existentes.",
                    },
                },
            ],
        });

        window.tourActsList = tourActsList;

        var tourActsListShown = localStorage.getItem("tourActsListShown");
        if (!tourActsListShown) {
            setTimeout(function () {
                tourActsList.drive();
                localStorage.setItem("tourActsListShown", "true");
            }, 1000);
        }
    }

    // ========================================================
    // TOUR 5: CREAR ACTO ADMINISTRATIVO
    // ========================================================
    if (isActsCreate) {
        var tourActsCreate = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "REGISTRAR UN ACTO ADMINISTRATIVO",
                        description:
                            "Completa este formulario para registrar un nuevo acto administrativo. Los campos con * son obligatorios. Puedes adjuntar los documentos PDF al final.",
                    },
                },
                {
                    element: "[data-tour='act-unidad']",
                    popover: {
                        title: "Paso 1: Unidad Organizacional",
                        description:
                            "Selecciona la oficina o dependencia que esta registrando el acto administrativo. <strong>Si eres usuario, ya esta seleccionada tu unidad automaticamente.</strong>",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='act-vigencia']",
                    popover: {
                        title: "Paso 2: Vigencia del Acto",
                        description:
                            "El año de vigencia del acto administrativo. Este valor se usa para agrupar y filtrar los actos por año.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='act-consecutivo']",
                    popover: {
                        title: "Paso 3: Numero de Consecutivo",
                        description:
                            "El numero de consecutivo se digitará <strong>automaticamente</strong> basado en la unidad, serie y subserie seleccionadas. Este numero permite ubicar el documento en el sistema.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='act-serie']",
                    popover: {
                        title: "Paso 4: Serie del Acto",
                        description:
                            "Selecciona la serie documental que clasifica el acto administrativo. Ejemplo: 'Resoluciones', 'Decretos', 'Circulares', etc.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='act-subserie']",
                    popover: {
                        title: "Paso 5: Subserie del Acto",
                        description:
                            "Selecciona la subserie correspondiente dentro de la serie elegida. Las opciones dependen de la serie seleccionada en el paso anterior.",
                        side: "right",
                    },
                },
                {
                    element: "[data-tour='act-asunto']",
                    popover: {
                        title: "Paso 6: Objeto / Asunto",
                        description:
                            "Describe brevemente de que trata el acto administrativo. <strong>Ejemplo:</strong> 'Por la cual se nombra al director de...' o 'Regulacion del proceso de...'.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='act-notas']",
                    popover: {
                        title: "Paso 7: Notas (Opcional)",
                        description:
                            "Si necesitas agregar informacion adicional, observaciones o comentarios sobre el acto, escribelos aqui.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='act-adjuntos']",
                    popover: {
                        title: "Paso 8: Archivos Adjuntos",
                        description:
                            "Sube los documentos PDF del acto administrativo. Puedes subir multiples archivos <strong>(maximo 20MB cada uno)</strong>. Los archivos quedan disponibles para descarga y consulta.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='act-folios']",
                    popover: {
                        title: "Folios",
                        description:
                            "Al subir archivos PDF, se calculará <strong>automaticamente</strong> el numero total de folios de los documentos.",
                        side: "top",
                    },
                },
                {
                    element: ".fi-form-actions",
                    popover: {
                        title: "Paso 9: Guardar",
                        description:
                            "Cuando hayas completado toda la informacion, haz clic en 'Crear' para guardar el acto administrativo en el sistema.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='help-button-acts-create']",
                    popover: {
                        title: "¿Necesitas ayuda?",
                        description:
                            "Puedes ver este tutorial de nuevo en cualquier momento haciendo clic aqui.",
                        side: "bottom",
                    },
                },
                {
                    popover: {
                        title: "¡YA ESTÁS LISTO!",
                        description:
                            "Completa los datos y el acto administrativo quedara registrado. Recuerda que puedes editarlo despues si necesitas hacer cambios.",
                    },
                },
            ],
        });

        window.tourActsCreate = tourActsCreate;

        var tourActsCreateShown = localStorage.getItem("tourActsCreateShown");
        if (!tourActsCreateShown) {
            setTimeout(function () {
                tourActsCreate.drive();
                localStorage.setItem("tourActsCreateShown", "true");
            }, 1000);
        }
    }

    // ========================================================
    // FUNCIONES GLOBALES
    // ========================================================

    // Reiniciar todos los tours (para volver a mostrarlos)
    window.reiniciarTourInventario = function () {
        localStorage.removeItem("tourInventarioDashboardShown");
        localStorage.removeItem("tourInventoryListShown");
        localStorage.removeItem("tourInventoryCreateShown");
        localStorage.removeItem("tourActsListShown");
        localStorage.removeItem("tourActsCreateShown");
        location.reload();
    };

    // Iniciar el tour de la pagina actual manualmente
    window.iniciarTour = function () {
        if (window.tourInventoryList) {
            window.tourInventoryList.drive();
        } else if (window.tourInventoryCreate) {
            window.tourInventoryCreate.drive();
        } else if (window.tourActsList) {
            window.tourActsList.drive();
        } else if (window.tourActsCreate) {
            window.tourActsCreate.drive();
        } else if (window.tourDashboard) {
            window.tourDashboard.drive();
        }
    };
});
