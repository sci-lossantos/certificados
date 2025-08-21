import React from "react"
// Componente DirectorioPreview para navegador
function DirectorioPreview({ escuelaInfo, participantes, cursoNombre, numeroRegistro, fechaInicio, fechaFin }) {
  const formatDate = (dateString) => {
    const date = new Date(dateString)
    const day = date.getDate()
    const month = date.toLocaleDateString("es-ES", { month: "long" })
    const year = date.getFullYear()
    return `${day} DE ${month.toUpperCase()} DE ${year}`
  }

  const formatDateShort = (dateString) => {
    const date = new Date(dateString)
    return date.toLocaleDateString("es-ES", {
      day: "2-digit",
      month: "2-digit",
      year: "numeric",
    })
  }

  // Encabezado que se repite en cada página
  const PageHeader = () => {
    return React.createElement(
      "div",
      { className: "border-2 border-black p-3 mb-6" },
      React.createElement(
        "div",
        { className: "grid grid-cols-3 gap-4 items-start text-xs" },
        React.createElement(
          "div",
          { className: "text-left" },
          React.createElement("div", { className: "font-bold" }, `Código: ${escuelaInfo.codigo_formato}`),
          React.createElement("div", { className: "font-bold mt-1" }, "FORMATO DIRECTORIO FINALIZACIÓN DE CURSO"),
        ),
        React.createElement(
          "div",
          { className: "text-center" },
          React.createElement("div", { className: "font-bold text-sm leading-tight" }, escuelaInfo.nombre_completo),
          escuelaInfo.nombre_estacion &&
            React.createElement(
              "div",
              { className: "font-bold text-sm leading-tight mt-1" },
              escuelaInfo.nombre_estacion,
            ),
          React.createElement("div", { className: "font-bold text-sm leading-tight mt-1" }, escuelaInfo.nombre),
        ),
        React.createElement(
          "div",
          { className: "text-right" },
          React.createElement("div", { className: "font-bold" }, `Versión: ${escuelaInfo.version_formato}`),
          React.createElement("div", { className: "font-bold mt-1" }, "Vigente Desde:"),
          React.createElement(
            "div",
            { className: "font-bold" },
            escuelaInfo.fecha_vigencia
              ? formatDateShort(escuelaInfo.fecha_vigencia)
              : formatDateShort(new Date().toISOString()),
          ),
        ),
      ),
    )
  }

  // Crear páginas
  const pages = []

  // Portada
  pages.push(
    React.createElement(
      "div",
      { key: "portada", className: "min-h-screen flex flex-col page-break" },
      React.createElement(PageHeader),
      React.createElement(
        "div",
        { className: "flex-1 flex flex-col justify-center items-center text-center py-20" },
        React.createElement("h1", { className: "text-8xl font-bold mb-32 tracking-wider" }, "DIRECTORIO"),
        React.createElement(
          "div",
          { className: "space-y-6 text-xl max-w-4xl" },
          React.createElement("div", { className: "font-bold text-2xl leading-relaxed" }, cursoNombre.toUpperCase()),
          React.createElement("div", { className: "font-bold text-xl" }, `REGISTRO ${numeroRegistro}`),
          React.createElement(
            "div",
            { className: "font-bold text-xl" },
            `DEL ${formatDate(fechaInicio)} AL ${formatDate(fechaFin)}`,
          ),
        ),
      ),
      React.createElement(
        "div",
        { className: "text-center text-lg font-bold leading-relaxed mt-20" },
        escuelaInfo.pie_pagina
          ? React.createElement("div", { className: "whitespace-pre-line" }, escuelaInfo.pie_pagina)
          : React.createElement("div", {}, escuelaInfo.nombre_completo, React.createElement("br"), escuelaInfo.nombre),
      ),
    ),
  )

  // Páginas de participantes (4 por página)
  for (let pageIndex = 0; pageIndex < Math.ceil(participantes.length / 4); pageIndex++) {
    const startIndex = pageIndex * 4
    const pageParticipants = participantes.slice(startIndex, startIndex + 4)

    const participantElements = pageParticipants.map((participante, index) => {
      const numeroGlobal = startIndex + index + 1

      return React.createElement(
        "div",
        { key: `participante-${numeroGlobal}`, className: "border-2 border-black p-4" },
        React.createElement(
          "div",
          { className: "grid grid-cols-12 gap-4" },
          // Número del participante
          React.createElement(
            "div",
            { className: "col-span-1 flex items-start justify-center" },
            React.createElement("div", { className: "text-2xl font-bold mt-2" }, numeroGlobal),
          ),
          // Información del participante
          React.createElement(
            "div",
            { className: "col-span-11 space-y-3" },
            // Primera fila: Nombres y Cédula
            React.createElement(
              "div",
              { className: "grid grid-cols-2 gap-8" },
              React.createElement(
                "div",
                {},
                React.createElement("div", { className: "font-bold text-sm mb-1" }, "NOMBRES Y APELLIDOS:"),
                React.createElement(
                  "div",
                  { className: "font-bold text-lg leading-tight" },
                  (participante.nombres + " " + participante.apellidos).toUpperCase(),
                ),
              ),
              React.createElement(
                "div",
                {},
                React.createElement("div", { className: "font-bold text-sm mb-1" }, "CEDULA"),
                React.createElement("div", { className: "text-lg font-bold" }, participante.cedula),
              ),
            ),
            // Segunda fila: Entidad
            React.createElement(
              "div",
              {},
              React.createElement("div", { className: "font-bold text-sm mb-1" }, "ENTIDAD:"),
              React.createElement(
                "div",
                { className: "font-bold text-base leading-tight" },
                (participante.entidad || "NO ESPECIFICADA").toUpperCase(),
              ),
            ),
            // Tercera fila: Email y Celular
            React.createElement(
              "div",
              { className: "grid grid-cols-2 gap-8" },
              React.createElement(
                "div",
                {},
                React.createElement("div", { className: "font-bold text-sm mb-1" }, "E-MAIL:"),
                React.createElement("div", { className: "text-base" }, participante.email || ""),
              ),
              React.createElement(
                "div",
                {},
                React.createElement("div", { className: "font-bold text-sm mb-1" }, "CELULAR:"),
                React.createElement("div", { className: "text-base" }, participante.celular || ""),
              ),
            ),
          ),
        ),
      )
    })

    pages.push(
      React.createElement(
        "div",
        { key: `page-${pageIndex}`, className: "min-h-screen page-break" },
        React.createElement(PageHeader),
        pageIndex === 0 &&
          React.createElement(
            "div",
            { className: "text-center mb-8" },
            React.createElement("h2", { className: "text-2xl font-bold" }, "PARTICIPANTES"),
          ),
        React.createElement("div", { className: "space-y-8" }, ...participantElements),
      ),
    )
  }

  // Página de Coordinador e Instructores
  pages.push(
    React.createElement(
      "div",
      { key: "coordinadores", className: "min-h-screen page-break" },
      React.createElement(PageHeader),
      React.createElement(
        "div",
        { className: "mb-8" },
        React.createElement("h2", { className: "text-2xl font-bold" }, "COORDINADOR E INSTRUCTORES"),
      ),
      React.createElement(
        "div",
        { className: "space-y-8" },
        React.createElement(
          "div",
          { className: "border-2 border-black p-4" },
          React.createElement(
            "div",
            { className: "grid grid-cols-12 gap-4" },
            React.createElement(
              "div",
              { className: "col-span-1 flex items-start justify-center" },
              React.createElement("div", { className: "text-2xl font-bold mt-2" }, "1"),
            ),
            React.createElement(
              "div",
              { className: "col-span-11 space-y-3" },
              React.createElement(
                "div",
                { className: "grid grid-cols-2 gap-8" },
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "NOMBRES Y APELLIDOS:"),
                  React.createElement("div", { className: "font-bold text-lg leading-tight" }, "JORGE ELIECER SERRANO"),
                ),
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "CEDULA"),
                  React.createElement("div", { className: "text-lg font-bold" }, "91355840"),
                ),
              ),
              React.createElement(
                "div",
                {},
                React.createElement("div", { className: "font-bold text-sm mb-1" }, "ENTIDAD:"),
                React.createElement(
                  "div",
                  { className: "font-bold text-base leading-tight" },
                  "CUERPO BOMBEROS VOLUNTARIOS LOS SANTOS",
                ),
              ),
              React.createElement(
                "div",
                { className: "grid grid-cols-2 gap-8" },
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "E-MAIL:"),
                  React.createElement("div", { className: "text-base" }, "direccionacademica@esiboc.edu.co"),
                ),
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "CELULAR:"),
                  React.createElement("div", { className: "text-base" }, "3003272507"),
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  )

  // Página de Logística
  pages.push(
    React.createElement(
      "div",
      { key: "logistica", className: "min-h-screen page-break" },
      React.createElement(PageHeader),
      React.createElement(
        "div",
        { className: "mb-8" },
        React.createElement("h2", { className: "text-2xl font-bold" }, "LOGISTICA"),
      ),
      React.createElement(
        "div",
        { className: "space-y-8" },
        React.createElement(
          "div",
          { className: "border-2 border-black p-4" },
          React.createElement(
            "div",
            { className: "grid grid-cols-12 gap-4" },
            React.createElement(
              "div",
              { className: "col-span-1 flex items-start justify-center" },
              React.createElement("div", { className: "text-2xl font-bold mt-2" }, "1"),
            ),
            React.createElement(
              "div",
              { className: "col-span-11 space-y-3" },
              React.createElement(
                "div",
                { className: "grid grid-cols-2 gap-8" },
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "NOMBRES Y APELLIDOS:"),
                  React.createElement("div", { className: "font-bold text-lg leading-tight" }, "JORGE ELIECER SERRANO"),
                ),
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "CEDULA"),
                  React.createElement("div", { className: "text-lg font-bold" }, "91355840"),
                ),
              ),
              React.createElement(
                "div",
                {},
                React.createElement("div", { className: "font-bold text-sm mb-1" }, "ENTIDAD:"),
                React.createElement(
                  "div",
                  { className: "font-bold text-base leading-tight" },
                  "CUERPO BOMBEROS VOLUNTARIOS LOS SANTOS",
                ),
              ),
              React.createElement(
                "div",
                { className: "grid grid-cols-2 gap-8" },
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "E-MAIL:"),
                  React.createElement("div", { className: "text-base" }, "direccionacademica@esiboc.edu.co"),
                ),
                React.createElement(
                  "div",
                  {},
                  React.createElement("div", { className: "font-bold text-sm mb-1" }, "CELULAR:"),
                  React.createElement("div", { className: "text-base" }, "3003272507"),
                ),
              ),
            ),
          ),
        ),
      ),
    ),
  )

  // Estilos para impresión
  const styles = React.createElement(
    "style",
    {},
    `
    @media print {
      .page-break {
        page-break-before: always;
      }
      .page-break:first-child {
        page-break-before: auto;
      }
    }
    `,
  )

  return React.createElement("div", { className: "bg-white text-black font-sans" }, styles, ...pages)
}

// Hacer disponible globalmente
window.DirectorioPreview = DirectorioPreview
