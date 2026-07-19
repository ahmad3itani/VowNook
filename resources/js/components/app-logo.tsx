export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md border border-[#dce2d8] bg-[#f1f0ea]">
                <img src="/images/brand/logo-mark.svg" alt="VowNook" className="size-3/4 object-contain" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="mb-0.5 truncate font-['Newsreader'] text-base leading-tight font-semibold tracking-tight">
                    VowNook
                </span>
            </div>
        </>
    );
}
