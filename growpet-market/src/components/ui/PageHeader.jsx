import SectionHeader from './SectionHeader'

function PageHeader({ eyebrow, title, description, action, align = 'left' }) {
  return (
    <div className={`page-header page-header--${align}`}>
      <SectionHeader
        eyebrow={eyebrow}
        title={title}
        description={description}
        align={align}
      />
      {action && <div className="page-header__action">{action}</div>}
    </div>
  )
}

export default PageHeader
