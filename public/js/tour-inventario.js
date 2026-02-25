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
    // Un mini-tour por cada paso del wizard, disparado automáticamente
    // cuando el usuario avanza de paso (MutationObserver en fi-active).
    // ========================================================
    if (isActsCreate) {

        // ── Tour del Paso 1: Clasificación ────────────────────────────────
        var tourActsStep1 = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "Paso 1 — Clasificación",
                        description:
                            "Selecciona la <strong>dependencia</strong>, la <strong>serie</strong> " +
                            "y la <strong>subserie</strong> del acto según el CCD. " +
                            "El consecutivo se genera automáticamente al guardar.",
                    },
                },
                {
                    element: "[data-tour='act-unidad']",
                    popover: {
                        title: "Unidad Organizacional",
                        description:
                            "Selecciona la dependencia que registra el acto. " +
                            "Si eres usuario normal, ya aparece seleccionada tu unidad automáticamente.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='act-vigencia']",
                    popover: {
                        title: "Vigencia",
                        description:
                            "Año al que corresponde el acto. " +
                            "Se usa para generar el consecutivo y filtrar por año.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='act-serie']",
                    popover: {
                        title: "Serie Documental",
                        description:
                            "Selecciona la serie según el CCD. Ejemplo: " +
                            "<em>03 - Actos Administrativos</em> o <em>24 - Comunicaciones Oficiales</em>.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='act-subserie']",
                    popover: {
                        title: "Subserie Documental",
                        description:
                            "Selecciona la subserie: Resoluciones, Decretos, Circulares, " +
                            "Comunicaciones Externas o Internas. " +
                            "Las opciones dependen de la serie elegida.",
                        side: "bottom",
                    },
                },
                {
                    element: "[data-tour='act-consecutivo']",
                    popover: {
                        title: "Consecutivo (automático)",
                        description:
                            "El número de consecutivo se genera automáticamente. " +
                            "Formato: <strong>2026.DA.03.02.001</strong>. " +
                            "No debes escribirlo.",
                        side: "top",
                    },
                },
                {
                    popover: {
                        title: "¡Listo el Paso 1!",
                        description:
                            "Completa los campos y haz clic en " +
                            "<strong>Siguiente →</strong> para ir al Paso 2: Detalle.",
                    },
                },
            ],
        });

        // ── Tour del Paso 2: Detalle ──────────────────────────────────────
        var tourActsStep2 = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "Paso 2 — Detalle del Acto",
                        description:
                            "Describe el acto administrativo. " +
                            "El <strong>Objeto / Asunto</strong> es obligatorio; " +
                            "las <strong>Notas</strong> son opcionales.",
                    },
                },
                {
                    element: "[data-tour='act-asunto']",
                    popover: {
                        title: "Objeto / Asunto",
                        description:
                            "Escribe de qué trata el acto. Ejemplo: " +
                            "<em>'Por la cual se reglamenta el proceso de contratación...'</em>",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='act-notas']",
                    popover: {
                        title: "Notas (opcional)",
                        description:
                            "Observaciones o información adicional sobre el acto. " +
                            "Puedes dejarlo vacío.",
                        side: "top",
                    },
                },
                {
                    popover: {
                        title: "¡Listo el Paso 2!",
                        description:
                            "Haz clic en <strong>Siguiente →</strong> para ir al Paso 3: Documentos.",
                    },
                },
            ],
        });

        // ── Tour del Paso 3: Documentos ───────────────────────────────────
        var tourActsStep3 = driverFn({
            showProgress: true,
            nextBtnText: "Siguiente",
            prevBtnText: "Anterior",
            doneBtnText: "Entendido",
            progressText: "Paso {{current}} de {{total}}",
            steps: [
                {
                    popover: {
                        title: "Paso 3 — Documentos Adjuntos",
                        description:
                            "Adjunta los <strong>documentos PDF</strong> del acto (opcional). " +
                            "Cuando termines haz clic en <strong>Guardar</strong>.",
                    },
                },
                {
                    element: "[data-tour='act-adjuntos']",
                    popover: {
                        title: "Documentos PDF",
                        description:
                            "Sube uno o varios archivos PDF. " +
                            "Máximo <strong>20 MB</strong> por archivo.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='act-folios']",
                    popover: {
                        title: "Folios (automático)",
                        description:
                            "El número total de páginas de los PDF se calcula automáticamente.",
                        side: "top",
                    },
                },
                {
                    element: "[data-tour='help-button-acts-create']",
                    popover: {
                        title: "¿Necesitas ayuda?",
                        description:
                            "Puedes volver a ver el tour de cualquier paso haciendo clic aquí.",
                        side: "bottom",
                    },
                },
                {
                    popover: {
                        title: "¡Todo listo!",
                        description:
                            "Haz clic en <strong>Guardar</strong> para registrar el acto " +
                            "con su consecutivo único.",
                    },
                },
            ],
        });

        window.tourActsStep1 = tourActsStep1;
        window.tourActsStep2 = tourActsStep2;
        window.tourActsStep3 = tourActsStep3;
        // Compatibilidad con iniciarTour global
        window.tourActsCreate = tourActsStep1;

        // ── Arrancar tour del paso 1 al cargar ────────────────────────────
        var tourActsCreateShown = localStorage.getItem("tourActsCreateShown");
        if (!tourActsCreateShown) {
            setTimeout(function () {
                tourActsStep1.drive();
                localStorage.setItem("tourActsCreateShown", "true");
            }, 1000);
        }

        // ── MutationObserver: disparar tour cuando el wizard avanza ───────
        // Las clases de paso activo/inactivo las pone Alpine en .fi-fo-wizard-step
        var wizardStepToured = 0; // índice del último paso que ya lanzó su tour

        var wizardStepEls = document.querySelectorAll(".fi-fo-wizard-step");

        if (wizardStepEls.length) {
            var stepObserver = new MutationObserver(function (mutations) {
                mutations.forEach(function (mutation) {
                    if (mutation.type !== "attributes") return;

                    var el = mutation.target;
                    // Solo nos interesa cuando un paso SE ACTIVA
                    if (!el.classList.contains("fi-active")) return;

                    var idx = Array.from(wizardStepEls).indexOf(el);

                    if (idx === 1 && wizardStepToured < 1) {
                        wizardStepToured = 1;
                        // Pequeño delay para que el wizard termine la transición
                        setTimeout(function () {
                            tourActsStep2.drive();
                        }, 350);
                    } else if (idx === 2 && wizardStepToured < 2) {
                        wizardStepToured = 2;
                        setTimeout(function () {
                            tourActsStep3.drive();
                        }, 350);
                    }
                });
            });

            wizardStepEls.forEach(function (el) {
                stepObserver.observe(el, {
                    attributes: true,
                    attributeFilter: ["class"],
                });
            });
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
    // En la página de crear acto, lanza el tour del paso activo del wizard
    window.iniciarTour = function () {
        if (window.tourActsStep1) {
            var activeStep = document.querySelector(".fi-fo-wizard-step.fi-active");
            var allSteps = document.querySelectorAll(".fi-fo-wizard-step");
            var idx = activeStep ? Array.from(allSteps).indexOf(activeStep) : 0;

            if (idx === 1 && window.tourActsStep2) {
                window.tourActsStep2.drive();
            } else if (idx === 2 && window.tourActsStep3) {
                window.tourActsStep3.drive();
            } else {
                window.tourActsStep1.drive();
            }
            return;
        }

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
