"use client"

interface CertificatePreviewProps {
  configId?: string
}

export default function CertificatePreview({ configId }: CertificatePreviewProps) {
  // Datos de ejemplo basados en los certificados que proporcionaste
  const sampleData = {
    participante: {
      nombres: "ARLEZ JUNIOR",
      apellidos: "ARBOLEDA CARVAJAL",
      cedula: "91447273",
    },
    curso: {
      nombre: "SISTEMA COMANDO DE INCIDENTES BÁSICO PARA BOMBEROS",
      registro: "184-2025",
      duracion: "24 HORAS",
      fechaInicio: "21 de Julio",
      fechaFin: "23 de Julio",
      año: "2025",
      lugar: "Floridablanca – Santander",
    },
    acta: {
      numero: "021",
      fecha: "24 Julio de 2025",
    },
    consecutivo: "2025-184-01",
  }

  return (
    <div className="bg-white min-h-screen">
      {/* Certificado Principal */}
      <div className="max-w-4xl mx-auto p-8 border-2 border-gray-800" style={{ aspectRatio: "210/297" }}>
        {/* Encabezado */}
        <div className="text-center mb-8">
          <div className="flex justify-between items-start mb-4">
            <div className="text-left text-sm">
              <p className="font-bold">Cuerpo de Bomberos Los Santos Santander</p>
              <p className="font-bold">Escuela Internacional de Bomberos del Oriente Colombiano</p>
              <p className="font-bold">ESIBOC</p>
            </div>
            <div className="text-center">
              <div className="w-16 h-16 bg-gray-200 rounded-full mx-auto mb-2"></div>
              <p className="text-xs">LOGO</p>
            </div>
            <div className="text-right text-sm">
              <p className="font-bold">CT. EN JEFE LINA MARÍA MARÍN RODRÍGUEZ</p>
              <p>Directora Nacional DNBC</p>
              <p className="font-bold mt-2">CT. MANUEL ENRIQUE SALAZAR HERNANDEZ</p>
              <p>Comandante Cuerpo de Bomberos Los Santos Sant.</p>
            </div>
          </div>
        </div>

        {/* Contenido Principal */}
        <div className="text-center space-y-6">
          {/* Nombre del participante */}
          <div className="text-2xl font-bold border-b-2 border-gray-800 pb-2 mb-8">
            {sampleData.participante.nombres} {sampleData.participante.apellidos}
          </div>

          {/* Cédula */}
          <div className="text-right text-lg font-bold mb-8">{sampleData.participante.cedula}</div>

          {/* Texto principal */}
          <div className="space-y-4 text-justify">
            <p>
              <strong>
                Bajo acta número {sampleData.acta.numero} del {sampleData.acta.fecha} del Cuerpo de Bomberos Voluntarios
                Los Santos
              </strong>
            </p>
            <p>
              <strong>Con una duración de: {sampleData.curso.duracion}</strong>
            </p>
            <p>
              <strong>Certifica que:</strong>
            </p>
            <p>
              <strong>Identificado con C.C. No.</strong>
            </p>
            <p>
              <strong>Asistió y aprobó los requisitos del Curso:</strong>
            </p>
            <p>
              Curso autorizado bajo registro Nro. {sampleData.curso.registro} de la Dirección Nacional de Bomberos
              Colombia
            </p>
            <p>En constancia de lo anterior, se firma a los 24 dias del mes de Julio de {sampleData.curso.año}</p>
          </div>

          {/* Nombre del curso */}
          <div className="text-xl font-bold my-8 p-4 border border-gray-400">{sampleData.curso.nombre}</div>

          {/* Número consecutivo */}
          <div className="text-right">
            <div className="inline-block">
              <p className="text-sm">No consecutivo</p>
              <p className="font-bold text-lg">{sampleData.consecutivo}</p>
              <p className="text-sm">Certificado</p>
            </div>
          </div>

          {/* Decoración lateral */}
          <div className="absolute right-4 top-1/2 transform -translate-y-1/2 -rotate-90">
            <div className="text-4xl font-bold tracking-widest text-gray-300">BOMBEROS DE COLOMBIA</div>
          </div>

          {/* Información del lugar y fecha */}
          <div className="mt-8 text-center">
            <p>
              Realizado en ({sampleData.curso.lugar}) del ({sampleData.curso.fechaInicio}) al (
              {sampleData.curso.fechaFin}) de {sampleData.curso.año}
            </p>
          </div>

          {/* Pie de página */}
          <div className="mt-8 text-center text-sm">
            <p className="font-bold">ESIBOC-CURSOS</p>
          </div>
        </div>
      </div>

      {/* Segunda página - Contenido Programático */}
      <div className="max-w-4xl mx-auto p-8 border-2 border-gray-800 mt-8" style={{ aspectRatio: "210/297" }}>
        <div className="text-center mb-8">
          <h2 className="text-xl font-bold">CONTENIDO PROGRAMATICO</h2>
          <p className="text-sm mt-2">ST. JORGE E. SERRANO PRADA</p>
          <p className="text-sm">Coordinador Curso</p>
        </div>

        <div className="grid grid-cols-2 gap-8 text-sm">
          <div>
            <h3 className="font-bold mb-4">1. INTRODUCCIÓN</h3>
            <ul className="list-disc list-inside space-y-1">
              <li>Propósito</li>
              <li>Objetivos de desempeño</li>
              <li>Objetivos de capacitación</li>
              <li>Evaluaciones</li>
              <li>Método</li>
              <li>Reglas para participar</li>
            </ul>

            <h3 className="font-bold mb-4 mt-6">2. ORIENTACIÓN E IMPLEMENTACIÓN DEL SCI</h3>
            <ul className="list-disc list-inside space-y-1">
              <li>Contribución del SCI</li>
              <li>Antecedentes del SCI</li>
              <li>SCI como norma ISO</li>
              <li>Ruta de Implementación</li>
              <li>Cómo abordar las cinco fases del SCI</li>
              <li>Detalle de acciones a desarrollar</li>
              <li>Documentación</li>
            </ul>

            <h3 className="font-bold mb-4 mt-6">3. CARACTERÍSTICAS Y PRINCIPIOS DEL SCI</h3>
            <ul className="list-disc list-inside space-y-1">
              <li>Los incidentes y el SCI</li>
              <li>El SCI: un marco común de atención</li>
              <li>Definiciones relacionadas con el SCI</li>
              <li>Aplicaciones del SCI Conceptos Principios y Características del SCI</li>
            </ul>
          </div>

          <div>
            <h3 className="font-bold mb-4">4. FUNCIONES, RESPONSABILIDADES Y CARACTERÍSTICAS DEL SCI</h3>
            <ul className="list-disc list-inside space-y-1">
              <li>Funciones y responsabilidades</li>
              <li>Organigrama del SCI</li>
              <li>Staff de Comando y Secciones</li>
              <li>Delegación de funciones</li>
              <li>Terminología de la estructura</li>
            </ul>

            <h3 className="font-bold mb-4 mt-6">5. INSTALACIONES EN EL SCI</h3>
            <ul className="list-disc list-inside space-y-1">
              <li>Instalaciones en el SCI</li>
              <li>Tipos de Instalaciones</li>
              <li>Recursos en el SCI</li>
              <li>Clasificación y tipificación de recursos</li>
              <li>Categoría de los recursos</li>
              <li>Estado de los recursos</li>
            </ul>

            <h3 className="font-bold mb-4 mt-6">6. ¿CÓMO ESTABLECER EL SCI Y TRANSFERENCIA EN EL MANDO?</h3>
            <ul className="list-disc list-inside space-y-1">
              <li>Establecimiento del SCI</li>
              <li>Capacidad operativa</li>
              <li>Modos del mando</li>
              <li>Pasos para establecer el SCI</li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  )
}
