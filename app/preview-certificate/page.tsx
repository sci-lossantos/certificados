import CertificatePreview from "@/components/certificate-preview"

export default function PreviewCertificatePage({
  searchParams,
}: {
  searchParams: { config_id?: string }
}) {
  return <CertificatePreview configId={searchParams.config_id} />
}
