import AboutSection from '@/components/AboutSection';
import BpkbSection from '@/components/BpkbSection';
import CtaSection from '@/components/CtaSection';
import Footer from '@/components/Footer';
import HeroSection from '@/components/HeroSection';
import MotorSection from '@/components/MotorSection';
import ServicesSection from '@/components/ServicesSection';
import SimulationSection from '@/components/SimulationSection';

type Props = {
    onRequestAccess: () => void;
};

export default function PublicLandingView({ onRequestAccess }: Props) {
    return (
        <>
            <main>
                <HeroSection onRequestAccess={onRequestAccess} />
                <ServicesSection onRequestAccess={onRequestAccess} />
                <MotorSection onRequestAccess={onRequestAccess} />
                <BpkbSection onRequestAccess={onRequestAccess} />
                <SimulationSection onRequestAccess={onRequestAccess} />
                <AboutSection />
                <CtaSection onRequestAccess={onRequestAccess} />
            </main>
            <Footer onRequestAccess={onRequestAccess} />
        </>
    );
}
